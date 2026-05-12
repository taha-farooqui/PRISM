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

            // Phase 1: Script (5% -> 15%)
            $setPhase('Researching topic and outlining sections', 5);
            $this->info("[Phase 1] Generating script...");
            $script = $llm->generateScript($topic);

            if (!$script || empty($script['sections'])) {
                throw new \Exception('Script generation failed');
            }
            $setPhase('Compiling teaching script', 15);
            $this->info("Script: " . count($script['sections']) . " sections");

            // Phase 2: Audio (15% -> 30%)
            $setPhase('Recording narration', 20);
            $this->info("[Phase 2] Generating audio...");
            $audioDir = $renderer->getAudioDir();
            $audioResults = $audio->generateAll($script['sections'], $audioDir);

            if (empty($audioResults)) {
                throw new \Exception('Audio generation failed');
            }

            $audioPaths = array_column($audioResults, 'path');
            $durations = array_column($audioResults, 'duration');
            $totalDuration = array_sum($durations);
            $setPhase('Voice narration ready', 30);
            $this->info("Audio: " . count($audioResults) . " files, {$totalDuration}s total");

            // Phase 3: Manim Code (30% -> 50%)
            $setPhase('Designing animations and visuals', 35);
            $this->info("[Phase 3] Generating Manim code...");
            $code = $llm->generateManimCode($script, $durations);

            if (!$code) {
                throw new \Exception('Manim code generation failed');
            }
            $setPhase('Animation blueprint ready', 50);
            $this->info("Code: " . strlen($code) . " chars");

            // Phase 4: Render (50% -> 90%)
            $setPhase('Rendering animations frame by frame', 55);
            $this->info("[Phase 4] Rendering (with auto-repair)...");
            $rawVideoPath = $renderer->renderWithRepair($code, $llm);

            if (!$rawVideoPath) {
                throw new \Exception('Rendering failed after all retries');
            }
            $setPhase('Animations rendered', 90);
            $this->info("Raw video: {$rawVideoPath}");

            // Phase 5: Merge (90% -> 100%)
            $setPhase('Mixing audio with video', 92);
            $this->info("[Phase 5] Merging audio + video...");
            $finalPath = $renderer->mergeVideoAudio($rawVideoPath, $audioPaths, $topic);

            if (!$finalPath || !file_exists($finalPath)) {
                throw new \Exception('Video merge failed');
            }

            // Copy to public storage
            $storagePath = 'videos/' . $video->id . '.mp4';
            $publicDir = storage_path('app/public/videos');
            if (!is_dir($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            copy($finalPath, storage_path('app/public/' . $storagePath));

            $video->update([
                'status' => 'completed',
                'progress_phase' => 'Complete',
                'progress_percent' => 100,
                'video_path' => $storagePath,
            ]);

            Log::info("[PRISM] Complete! Video saved: {$storagePath}");
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
