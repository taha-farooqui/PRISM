<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AudioGenerationService
 *
 * Per-sentence audio generation.
 *
 * Pipeline:
 *   1. Split each section's narration into sentences.
 *   2. Generate TTS audio for EACH sentence in parallel (gTTS).
 *   3. Measure each sentence's exact duration.
 *   4. Concatenate sentences within a section into one MP3 + capture per-beat timings.
 *   5. Return a structured "beat timeline" — the Manim code generator uses these
 *      to pin animations to sentence boundaries instead of guessing.
 *
 * Return shape:
 *   [
 *     'sections' => [
 *       [
 *         'section_id' => 1,
 *         'path' => '/abs/path/to/section_01.mp3',
 *         'duration' => 18.3,           // total seconds for this section
 *         'beats' => [
 *           ['text' => 'Hello.', 'duration' => 1.2, 'start' => 0.0],
 *           ['text' => 'Today we learn X.', 'duration' => 2.5, 'start' => 1.2],
 *           ...
 *         ],
 *       ],
 *       ...
 *     ],
 *     'subtitle_vtt' => "WEBVTT\n\n00:00:00.000 --> 00:00:01.200\nHello.\n\n..."
 *   ]
 */
class AudioGenerationService
{
    private string $pythonBin;
    private const SENTENCE_GAP_SEC = 0.25; // small silence between sentences
    private const SECTION_GAP_SEC = 0.5;   // longer silence between sections

    public function __construct()
    {
        $this->pythonBin = 'python';
    }

    /**
     * Generate per-sentence audio for all sections.
     *
     * Returns enhanced result structure (see class doc).
     */
    public function generateAll(array $sections, string $outputDir): array
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Step 1: Build flat list of (section_id, sentence_idx, text, output_path) tasks.
        $sectionsMeta = [];
        $allTasks = [];
        $taskIdx = 0;

        foreach ($sections as $section) {
            $sid = $section['id'];
            $type = $section['section_type'] ?? 'section';

            // Prefer explicit beats from new script format. Fall back to splitting
            // narration into sentences for old-style scripts.
            $sentenceData = [];
            if (!empty($section['beats']) && is_array($section['beats'])) {
                foreach ($section['beats'] as $beat) {
                    $sentText = $this->cleanForTTS($beat['sentence'] ?? '');
                    $visual = $beat['visual'] ?? '';
                    if (trim($sentText) === '') continue;
                    $sentenceData[] = ['text' => $sentText, 'visual' => $visual];
                }
            } else {
                $narration = $section['narration'] ?? '';
                $cleaned = $this->cleanForTTS($narration);
                if (empty(trim($cleaned))) {
                    Log::warning("Empty narration for section {$sid}");
                    continue;
                }
                foreach ($this->splitIntoSentences($cleaned) as $s) {
                    $sentenceData[] = ['text' => $s, 'visual' => ''];
                }
            }

            if (empty($sentenceData)) {
                continue;
            }

            $sectionsMeta[$sid] = [
                'section_id' => $sid,
                'type' => $type,
                'sentence_paths' => [],
                'sentence_data' => $sentenceData,
            ];

            foreach ($sentenceData as $i => $sd) {
                $sentencePath = $outputDir . DIRECTORY_SEPARATOR
                    . sprintf('s%02d_b%02d.mp3', $sid, $i);
                $sectionsMeta[$sid]['sentence_paths'][] = $sentencePath;
                $allTasks[] = [
                    'idx' => $taskIdx++,
                    'section_id' => $sid,
                    'sentence_idx' => $i,
                    'text' => $sd['text'],
                    'path' => $sentencePath,
                ];
            }
        }

        if (empty($allTasks)) {
            return ['sections' => [], 'subtitle_vtt' => ''];
        }

        // Step 2: Generate all sentence audios in parallel.
        $results = $this->runParallelTTS($allTasks);

        // Map results back to (section_id, sentence_idx).
        $resultByIdx = [];
        foreach ($results as $r) {
            $resultByIdx[$r['idx']] = $r;
        }

        // Step 3: For each section, build the beat timeline and stitch a section MP3.
        $finalSections = [];
        $globalCursor = 0.0;
        $vttCues = [];

        foreach ($sectionsMeta as $sid => $meta) {
            $beats = [];
            $cursor = 0.0;
            $sentencePaths = [];

            foreach ($meta['sentence_data'] as $i => $sd) {
                $sentenceText = $sd['text'];
                $visual = $sd['visual'] ?? '';

                $tIdx = null;
                foreach ($allTasks as $t) {
                    if ($t['section_id'] === $sid && $t['sentence_idx'] === $i) {
                        $tIdx = $t['idx'];
                        break;
                    }
                }
                if ($tIdx === null || !isset($resultByIdx[$tIdx]) || !$resultByIdx[$tIdx]['ok']) {
                    Log::warning("Missing TTS for section {$sid} sentence {$i}");
                    continue;
                }
                $r = $resultByIdx[$tIdx];
                $dur = (float) $r['duration'];

                // Each beat: sentence text, the explicit visual to draw, exact duration and start
                $beats[] = [
                    'text' => $sentenceText,
                    'visual' => $visual,
                    'duration' => round($dur, 3),
                    'start' => round($cursor, 3),
                ];

                // VTT cue (using global time across whole video)
                $vttCues[] = [
                    'start' => $globalCursor + $cursor,
                    'end' => $globalCursor + $cursor + $dur,
                    'text' => $sentenceText,
                ];

                $sentencePaths[] = $r['path'];
                $cursor += $dur + self::SENTENCE_GAP_SEC;
            }

            // Stitch sentence MP3s into single section MP3 with small silence between
            $sectionPath = $outputDir . DIRECTORY_SEPARATOR
                . sprintf('section_%02d_%s.mp3', $sid, $meta['type']);
            $stitched = $this->stitchSectionAudio($sentencePaths, $sectionPath);

            if (!$stitched) {
                Log::warning("Failed to stitch section {$sid}, using first sentence as fallback");
                $sectionPath = $sentencePaths[0] ?? null;
            }

            $sectionDuration = $cursor - self::SENTENCE_GAP_SEC + self::SECTION_GAP_SEC;
            $sectionDuration = max(1.0, round($sectionDuration, 3));

            $finalSections[] = [
                'section_id' => $sid,
                'path' => $sectionPath,
                'duration' => $sectionDuration,
                'beats' => $beats,
            ];

            $globalCursor += $sectionDuration;
        }

        $vtt = $this->buildVtt($vttCues);

        return [
            'sections' => $finalSections,
            'subtitle_vtt' => $vtt,
        ];
    }

    /**
     * Split a paragraph of narration into sentences.
     * Handles common abbreviations and decimals.
     */
    private function splitIntoSentences(string $text): array
    {
        // Protect common abbreviations and decimals from being split
        $protected = preg_replace_callback(
            '/(\d+)\.(\d+)/',
            fn($m) => $m[1] . '<DOT>' . $m[2],
            $text
        );
        $protected = preg_replace('/\b(Mr|Mrs|Dr|St|vs|etc|i\.e|e\.g)\./i', '$1<DOT>', $protected);

        // Split on sentence terminators followed by whitespace + capital/digit
        $parts = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9])/u', $protected, -1, PREG_SPLIT_NO_EMPTY);

        $sentences = [];
        foreach ($parts as $p) {
            $clean = str_replace('<DOT>', '.', trim($p));
            if ($clean !== '') {
                $sentences[] = $clean;
            }
        }

        // If the splitter produced nothing (no terminators), treat as one sentence.
        if (empty($sentences) && trim($text) !== '') {
            $sentences[] = trim($text);
        }

        return $sentences;
    }

    /**
     * Run TTS tasks in parallel via a single Python subprocess (ThreadPoolExecutor).
     * Returns array of {idx, path, duration, ok, [error]} preserving the 'idx' key.
     */
    private function runParallelTTS(array $tasks): array
    {
        $payload = json_encode(array_map(fn($t) => [
            'idx' => $t['idx'],
            'text' => $t['text'],
            'path' => $t['path'],
        ], $tasks));

        $script = <<<'PYTHON'
import sys, json
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
sys.stderr.reconfigure(encoding='utf-8', errors='replace')

from concurrent.futures import ThreadPoolExecutor
from gtts import gTTS
import os

try:
    from mutagen.mp3 import MP3
    HAS_MUTAGEN = True
except ImportError:
    HAS_MUTAGEN = False

def generate(task):
    text = task['text']
    path = task['path']
    idx = task['idx']
    try:
        tts = gTTS(text=text, lang='en', slow=False)
        tts.save(path)
        if HAS_MUTAGEN:
            try:
                duration = MP3(path).info.length
            except Exception:
                duration = os.path.getsize(path) / 2000.0
        else:
            duration = os.path.getsize(path) / 2000.0
        return {'idx': idx, 'path': path, 'duration': float(duration), 'ok': True}
    except Exception as e:
        return {'idx': idx, 'path': path, 'duration': 0.0, 'ok': False, 'error': str(e)}

tasks = json.loads(sys.stdin.read())
with ThreadPoolExecutor(max_workers=min(12, len(tasks))) as ex:
    results = list(ex.map(generate, tasks))
print(json.dumps(results))
PYTHON;

        $tmpScript = tempnam(sys_get_temp_dir(), 'prism_tts_') . '.py';
        file_put_contents($tmpScript, $script);

        $cmd = sprintf('%s %s', escapeshellarg($this->pythonBin), escapeshellarg($tmpScript));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            @unlink($tmpScript);
            Log::error("Failed to start parallel TTS process");
            return [];
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        @unlink($tmpScript);

        $lines = array_filter(array_map('trim', explode("\n", $stdout)));
        $jsonLine = end($lines);
        $results = json_decode($jsonLine, true);

        if (!is_array($results)) {
            Log::error("Parallel TTS returned invalid JSON. stderr: " . substr($stderr, 0, 500));
            return [];
        }

        return $results;
    }

    /**
     * Stitch a list of sentence MP3s into one section MP3 using ffmpeg concat.
     * Inserts a SHORT silence between each sentence so audio feels natural.
     */
    private function stitchSectionAudio(array $sentencePaths, string $outputPath): bool
    {
        if (empty($sentencePaths)) {
            return false;
        }
        if (count($sentencePaths) === 1) {
            // Just copy the single sentence file
            return @copy($sentencePaths[0], $outputPath);
        }

        // Build a silent MP3 to insert between sentences
        $silenceMs = (int) (self::SENTENCE_GAP_SEC * 1000);
        $silenceFile = dirname($outputPath) . DIRECTORY_SEPARATOR . '_silence.mp3';
        if (!file_exists($silenceFile)) {
            $silenceCmd = sprintf(
                'ffmpeg -y -f lavfi -i anullsrc=channel_layout=mono:sample_rate=22050 -t %.3f -q:a 9 -acodec libmp3lame %s 2>&1',
                self::SENTENCE_GAP_SEC,
                escapeshellarg($silenceFile)
            );
            exec($silenceCmd);
        }

        // Build concat list interleaving sentence files with silence
        $listFile = dirname($outputPath) . DIRECTORY_SEPARATOR . '_section_list_' . uniqid() . '.txt';
        $lines = [];
        foreach ($sentencePaths as $i => $p) {
            if ($i > 0 && file_exists($silenceFile)) {
                $safe = str_replace('\\', '/', $silenceFile);
                $lines[] = "file '{$safe}'";
            }
            $safe = str_replace('\\', '/', $p);
            $lines[] = "file '{$safe}'";
        }
        file_put_contents($listFile, implode("\n", $lines));

        $cmd = sprintf(
            'ffmpeg -y -f concat -safe 0 -i %s -acodec libmp3lame -q:a 4 %s 2>&1',
            escapeshellarg($listFile),
            escapeshellarg($outputPath)
        );
        exec($cmd, $output, $code);
        @unlink($listFile);

        return $code === 0 && file_exists($outputPath);
    }

    /**
     * Build a WebVTT subtitle file from cue timestamps.
     */
    private function buildVtt(array $cues): string
    {
        $lines = ["WEBVTT", ""];
        foreach ($cues as $cue) {
            $lines[] = $this->formatVttTime($cue['start']) . ' --> ' . $this->formatVttTime($cue['end']);
            // VTT cue text must not contain blank lines; escape arrow chars
            $text = str_replace(["\n", "-->"], [' ', '-->'], $cue['text']);
            $lines[] = $text;
            $lines[] = '';
        }
        return implode("\n", $lines);
    }

    private function formatVttTime(float $seconds): string
    {
        $hours = (int) ($seconds / 3600);
        $minutes = (int) (($seconds % 3600) / 60);
        $secs = $seconds - ($hours * 3600) - ($minutes * 60);
        return sprintf('%02d:%02d:%06.3f', $hours, $minutes, $secs);
    }

    /**
     * Clean narration text for TTS: remove LaTeX, special chars, replace math symbols.
     */
    private function cleanForTTS(string $text): string
    {
        $text = preg_replace('/\\\\[a-zA-Z]+\{([^}]*)\}/', '$1', $text);
        $text = str_replace(['{}', '$', '^', '_', '\\'], '', $text);

        $replacements = [
            '>=' => ' greater than or equal to ',
            '<=' => ' less than or equal to ',
            '!=' => ' not equal to ',
            '==' => ' equals ',
            '**' => ' to the power of ',
            '*'  => ' times ',
            '/'  => ' divided by ',
            '+'  => ' plus ',
            '-'  => ' minus ',
            '='  => ' equals ',
        ];
        foreach ($replacements as $symbol => $word) {
            $text = str_replace($symbol, $word, $text);
        }

        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
