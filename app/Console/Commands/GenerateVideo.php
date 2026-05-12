<?php

namespace App\Console\Commands;

use App\Models\GeneratedVideo;
use App\Services\AudioGenerationService;
use App\Services\ManimRenderService;
use App\Services\VideoGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateVideo extends Command
{
    protected $signature = 'prism:generate-video {videoId} {topic}';
    protected $description = 'Run the full PRISM video generation pipeline in background';

    public function handle(): int
    {
        $videoId = $this->argument('videoId');
        $topic = $this->argument('topic');

        $video = GeneratedVideo::find($videoId);
        if (!$video) {
            $this->error("Video record {$videoId} not found");
            return 1;
        }

        $setPhase = function (string $phase, int $percent) use ($video) {
            $video->update([
                'progress_phase' => $phase,
                'progress_percent' => $percent,
            ]);
            Log::info("[PRISM] Phase: {$phase} ({$percent}%)");
        };

        try {
            $llm = new VideoGenerationService();
            $audio = new AudioGenerationService();
            $renderer = new ManimRenderService();

            // Phase 1: Script
            $setPhase('Researching topic and outlining sections', 5);
            $this->info("[Phase 1] Generating script...");
            $script = $llm->generateScript($topic);

            if (!$script || empty($script['sections'])) {
                throw new \Exception('Script generation failed');
            }
            $setPhase('Compiling teaching script', 15);
            $this->info("Script: " . count($script['sections']) . " sections");

            // Phase 2: Audio — per-sentence beats + VTT
            $setPhase('Recording narration sentence by sentence', 20);
            $this->info("[Phase 2] Generating per-sentence audio...");
            $audioDir = $renderer->getAudioDir();
            $audioResult = $audio->generateAll($script['sections'], $audioDir);

            $audioSections = $audioResult['sections'] ?? [];
            $vtt = $audioResult['subtitle_vtt'] ?? '';

            if (empty($audioSections)) {
                throw new \Exception('Audio generation failed');
            }

            $audioPaths = array_column($audioSections, 'path');
            $durations = array_column($audioSections, 'duration');
            $totalDuration = array_sum($durations);
            $beatCount = array_sum(array_map(fn($s) => count($s['beats'] ?? []), $audioSections));

            // Enforce 90-second minimum: pad the LAST section's last beat with extra
            // silence so the video has room to breathe even if the script came back short.
            $MIN_DURATION = 90.0;
            if ($totalDuration < $MIN_DURATION) {
                $shortBy = $MIN_DURATION - $totalDuration;
                $lastIdx = count($audioSections) - 1;
                if ($lastIdx >= 0) {
                    $lastBeats = $audioSections[$lastIdx]['beats'] ?? [];
                    if (!empty($lastBeats)) {
                        $bIdx = count($lastBeats) - 1;
                        $audioSections[$lastIdx]['beats'][$bIdx]['duration'] += $shortBy;
                        $audioSections[$lastIdx]['duration'] += $shortBy;
                        Log::info("[PRISM] Padded last beat by {$shortBy}s to hit 90s floor");
                    }
                }
                $totalDuration = $MIN_DURATION;
            }

            $setPhase('Voice narration ready', 30);
            $this->info("Audio: " . count($audioSections) . " sections, {$beatCount} beats, {$totalDuration}s total");

            // Phase 3: Manim Code (now passing audioSections with beats)
            $setPhase('Designing animations and visuals', 35);
            $this->info("[Phase 3] Generating Manim code...");
            $code = $llm->generateManimCode($script, $audioSections);

            if (!$code) {
                throw new \Exception('Manim code generation failed');
            }
            $setPhase('Animation blueprint ready', 50);
            $this->info("Code: " . strlen($code) . " chars");

            // Phase 4: Render
            $setPhase('Rendering animations frame by frame', 55);
            $this->info("[Phase 4] Rendering (with auto-repair)...");
            $rawVideoPath = $renderer->renderWithRepair($code, $llm);

            if (!$rawVideoPath) {
                throw new \Exception('Rendering failed after all retries');
            }
            $setPhase('Animations rendered', 90);
            $this->info("Raw video: {$rawVideoPath}");

            // Phase 5: Merge audio + video
            $setPhase('Mixing audio with video', 92);
            $this->info("[Phase 5] Merging audio + video...");
            $finalPath = $renderer->mergeVideoAudio($rawVideoPath, $audioPaths, $topic);

            if (!$finalPath || !file_exists($finalPath)) {
                throw new \Exception('Video merge failed');
            }

            // Phase 6: Save subtitles + copy outputs to public storage
            $setPhase('Generating subtitles', 96);
            $this->info("[Phase 6] Writing subtitle file...");
            $storagePath = 'videos/' . $video->id . '.mp4';
            $subtitleStoragePath = 'videos/' . $video->id . '.vtt';
            $publicDir = storage_path('app/public/videos');
            if (!is_dir($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            copy($finalPath, storage_path('app/public/' . $storagePath));

            if (!empty($vtt)) {
                file_put_contents(storage_path('app/public/' . $subtitleStoragePath), $vtt);
                $this->info("Subtitles: {$subtitleStoragePath}");
            } else {
                $subtitleStoragePath = null;
            }

            $video->update([
                'status' => 'completed',
                'progress_phase' => 'Complete',
                'progress_percent' => 100,
                'video_path' => $storagePath,
                'subtitle_path' => $subtitleStoragePath,
            ]);

            Log::info("[PRISM] Complete! Video: {$storagePath}, Subtitle: " . ($subtitleStoragePath ?? 'none'));
            $this->info("DONE! Video: {$storagePath}");

            return 0;

        } catch (\Exception $e) {
            Log::error("[PRISM] Pipeline failed: " . $e->getMessage());
            $this->error("Failed: " . $e->getMessage());
            $video->update([
                'status' => 'failed',
                'progress_phase' => 'Failed',
                'error_message' => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
