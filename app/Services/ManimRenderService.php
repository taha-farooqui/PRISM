<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ManimRenderService
 *
 * Handles:
 *   1. Saving generated Manim code to a .py file
 *   2. Running `python -m manim render` via subprocess
 *   3. Auto-repair loop (up to 3 retries via VideoGenerationService)
 *   4. Merging rendered video with audio via ffmpeg
 */
class ManimRenderService
{
    private string $pythonBin;
    private string $workDir;
    private string $mediaDir;
    private string $scriptPath;

    private const MAX_REPAIR_ATTEMPTS = 3;
    private const RENDER_TIMEOUT = 300; // seconds

    public function __construct()
    {
        $this->pythonBin = 'python';
        $this->workDir = storage_path('app/prism_render');
        $this->mediaDir = $this->workDir . DIRECTORY_SEPARATOR . 'media';
        $this->scriptPath = $this->workDir . DIRECTORY_SEPARATOR . 'generated_scene.py';

        // Ensure work directory exists
        if (!is_dir($this->workDir)) {
            mkdir($this->workDir, 0755, true);
        }
    }

    /**
     * Full render pipeline: save code, render with auto-repair, return video path.
     *
     * @param  string $code           Manim Python code
     * @param  VideoGenerationService $llm  For auto-repair calls
     * @return string|null  Path to rendered .mp4 or null on failure
     */
    public function renderWithRepair(string $code, VideoGenerationService $llm): ?string
    {
        $this->saveScript($code);

        $quality = 'l'; // 480p15
        $qualityName = '480p15';

        for ($attempt = 0; $attempt <= self::MAX_REPAIR_ATTEMPTS; $attempt++) {
            Log::info("Render attempt " . ($attempt + 1) . "/" . (1 + self::MAX_REPAIR_ATTEMPTS));

            [$success, $error] = $this->render($quality);

            if ($success) {
                $video = $this->findVideo($qualityName);
                if ($video) {
                    Log::info("Render successful: {$video}");
                    return $video;
                }
            }

            // Auto-repair
            if ($attempt < self::MAX_REPAIR_ATTEMPTS) {
                Log::warning("Render failed, attempting repair. Error: " . substr($error, 0, 300));
                $currentCode = $this->readScript();
                $fixed = $llm->repairManimCode($currentCode, $error);
                if ($fixed) {
                    $this->saveScript($fixed);
                    continue;
                }
            }

            Log::error("Render failed permanently: " . substr($error, 0, 400));
        }

        return null;
    }

    /**
     * Merge rendered video with audio files using ffmpeg.
     *
     * @param  string $videoPath  Path to rendered .mp4
     * @param  array  $audioPaths Array of audio file paths (one per section)
     * @param  string $topic      Topic name for output filename
     * @return string|null  Path to final merged video
     */
    public function mergeVideoAudio(string $videoPath, array $audioPaths, string $topic): ?string
    {
        if (empty($audioPaths)) {
            return $videoPath;
        }

        $validPaths = array_filter($audioPaths, fn($p) => !empty($p) && file_exists($p));
        if (empty($validPaths)) {
            return $videoPath;
        }

        // Output directory
        $outputDir = storage_path('app/public/videos');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $safeTopic = substr(preg_replace('/[^a-zA-Z0-9 _-]/', '_', $topic), 0, 50);
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . "PRISM_{$safeTopic}.mp4";

        // Step 1: Concatenate audio files
        $concatAudio = $this->mediaDir . DIRECTORY_SEPARATOR . '_combined.mp3';
        $concatAudio = $this->concatAudioFiles($validPaths, $concatAudio);

        if (!$concatAudio) {
            // Use first audio as fallback
            $concatAudio = reset($validPaths);
        }

        // Step 2: Merge video + audio
        //
        // Why we re-encode the video (instead of -c:v copy):
        //   - Manim renders MP4s where the moov atom (metadata index) is at the END
        //     of the file. Browsers can't seek/scrub until the whole file is buffered.
        //   - We re-encode with libx264 + -movflags +faststart, which moves the moov
        //     atom to the START so the browser can seek instantly.
        //   - We also force +genpts so timestamps are clean across the audio/video mux.
        $cmd = sprintf(
            'ffmpeg -y -fflags +genpts -i %s -i %s '
            . '-c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p '
            . '-c:a aac -b:a 128k '
            . '-movflags +faststart '
            . '-shortest -map 0:v:0 -map 1:a:0 %s 2>&1',
            escapeshellarg($videoPath),
            escapeshellarg($concatAudio),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1000) {
            return $outputPath;
        }

        Log::warning("ffmpeg merge failed: " . implode("\n", array_slice($output, -5)));
        return $videoPath; // Return raw video as fallback
    }

    /**
     * Get the working directory path.
     */
    public function getWorkDir(): string
    {
        return $this->workDir;
    }

    /**
     * Get the audio output directory path.
     */
    public function getAudioDir(): string
    {
        $dir = $this->mediaDir . DIRECTORY_SEPARATOR . 'audio';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    // ─────────────────────────────────────────────────────────
    // Internal: save/read script
    // ─────────────────────────────────────────────────────────

    private function saveScript(string $code): void
    {
        file_put_contents($this->scriptPath, $code);
    }

    private function readScript(): string
    {
        return file_exists($this->scriptPath) ? file_get_contents($this->scriptPath) : '';
    }

    // ─────────────────────────────────────────────────────────
    // Internal: render
    // ─────────────────────────────────────────────────────────

    private function render(string $quality): array
    {
        $cmd = sprintf(
            '%s -m manim render -q%s %s GenScene --disable_caching --no_latex_cleanup 2>&1',
            escapeshellarg($this->pythonBin),
            $quality,
            escapeshellarg($this->scriptPath)
        );

        $output = [];
        $returnCode = 0;

        // Use proc_open for timeout support
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $this->workDir);

        if (!is_resource($process)) {
            return [false, 'Failed to start manim process'];
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        $combined = $stdout . "\n" . $stderr;

        if ($returnCode === 0) {
            return [true, ''];
        }

        return [false, $combined ?: 'Unknown render error'];
    }

    // ─────────────────────────────────────────────────────────
    // Internal: find rendered video
    // ─────────────────────────────────────────────────────────

    private function findVideo(string $qualityName): ?string
    {
        $videoDir = $this->mediaDir
            . DIRECTORY_SEPARATOR . 'videos'
            . DIRECTORY_SEPARATOR . 'generated_scene'
            . DIRECTORY_SEPARATOR . $qualityName;

        if (!is_dir($videoDir)) {
            return null;
        }

        foreach (scandir($videoDir) as $file) {
            if (str_ends_with($file, '.mp4')) {
                return $videoDir . DIRECTORY_SEPARATOR . $file;
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────
    // Internal: concatenate audio files
    // ─────────────────────────────────────────────────────────

    private function concatAudioFiles(array $paths, string $outputPath): ?string
    {
        // Create concat list file
        $listFile = dirname($outputPath) . DIRECTORY_SEPARATOR . '_audiolist.txt';
        $lines = [];
        foreach ($paths as $p) {
            $safe = str_replace('\\', '/', $p);
            $lines[] = "file '{$safe}'";
        }
        file_put_contents($listFile, implode("\n", $lines));

        $cmd = sprintf(
            'ffmpeg -y -f concat -safe 0 -i %s -c copy %s 2>&1',
            escapeshellarg($listFile),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        // Cleanup list file
        @unlink($listFile);

        if ($returnCode === 0 && file_exists($outputPath)) {
            return $outputPath;
        }

        return null;
    }
}
