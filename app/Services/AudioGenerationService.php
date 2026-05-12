<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AudioGenerationService
 *
 * Generates TTS audio for sections using Python gTTS in PARALLEL via a single
 * Python subprocess that uses ThreadPoolExecutor. Much faster than per-section subprocess calls.
 */
class AudioGenerationService
{
    private string $pythonBin;

    public function __construct()
    {
        $this->pythonBin = 'python';
    }

    /**
     * Generate audio files for all sections in parallel.
     *
     * @param  array  $sections  The script sections array
     * @param  string $outputDir Directory to save audio files
     * @return array  [ ['path' => string, 'duration' => float], ... ]
     */
    public function generateAll(array $sections, string $outputDir): array
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Prepare task list
        $tasks = [];
        foreach ($sections as $section) {
            $id = $section['id'];
            $type = $section['section_type'] ?? 'section';
            $narration = $section['narration'] ?? '';

            $filename = sprintf('section_%02d_%s.mp3', $id, $type);
            $outputPath = $outputDir . DIRECTORY_SEPARATOR . $filename;

            $cleaned = $this->cleanForTTS($narration);
            if (empty(trim($cleaned))) {
                Log::warning("Empty narration for section {$id}");
                continue;
            }

            $tasks[] = [
                'text' => $cleaned,
                'path' => $outputPath,
            ];
        }

        if (empty($tasks)) {
            return [];
        }

        // Run all gTTS calls in parallel via single Python subprocess
        $results = $this->runParallelTTS($tasks);

        // Add audio buffer to each duration
        return array_map(fn($r) => [
            'path' => $r['path'],
            'duration' => ($r['duration'] ?? 5.0) + 0.5,
        ], $results);
    }

    /**
     * Run all TTS tasks in parallel via a single Python subprocess using ThreadPoolExecutor.
     * Returns array of ['path' => str, 'duration' => float] in input order.
     */
    private function runParallelTTS(array $tasks): array
    {
        // Build JSON payload of tasks
        $payload = json_encode(array_map(fn($t) => [
            'text' => $t['text'],
            'path' => $t['path'],
        ], $tasks));

        // Python script: read tasks from stdin, run gTTS in parallel, output JSON results
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
    try:
        tts = gTTS(text=text, lang='en', slow=False)
        tts.save(path)
        # Get duration
        if HAS_MUTAGEN:
            try:
                duration = MP3(path).info.length
            except Exception:
                duration = os.path.getsize(path) / 2000.0
        else:
            duration = os.path.getsize(path) / 2000.0
        return {'path': path, 'duration': float(duration), 'ok': True}
    except Exception as e:
        return {'path': path, 'duration': 0.0, 'ok': False, 'error': str(e)}

tasks = json.loads(sys.stdin.read())

# Use ThreadPoolExecutor for parallel network I/O (gTTS hits Google API)
results = []
with ThreadPoolExecutor(max_workers=min(8, len(tasks))) as ex:
    results = list(ex.map(generate, tasks))

print(json.dumps(results))
PYTHON;

        $tmpScript = tempnam(sys_get_temp_dir(), 'prism_tts_') . '.py';
        file_put_contents($tmpScript, $script);

        // Run with stdin pipe
        $cmd = sprintf('%s %s', escapeshellarg($this->pythonBin), escapeshellarg($tmpScript));

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
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

        // Parse results — JSON should be the LAST line of stdout
        $lines = array_filter(array_map('trim', explode("\n", $stdout)));
        $jsonLine = end($lines);
        $results = json_decode($jsonLine, true);

        if (!is_array($results)) {
            Log::error("Parallel TTS returned invalid JSON. stderr: " . substr($stderr, 0, 500));
            return [];
        }

        // Filter only successful results, log failures
        $valid = [];
        foreach ($results as $r) {
            if (!empty($r['ok']) && file_exists($r['path'])) {
                $valid[] = $r;
            } else {
                Log::warning("TTS failed for {$r['path']}: " . ($r['error'] ?? 'unknown'));
            }
        }

        return $valid;
    }

    /**
     * Clean narration text for TTS: remove LaTeX, special chars, replace math symbols.
     */
    private function cleanForTTS(string $text): string
    {
        // Remove LaTeX commands: \command{text} -> text
        $text = preg_replace('/\\\\[a-zA-Z]+\{([^}]*)\}/', '$1', $text);

        // Remove remaining LaTeX special chars
        $text = str_replace(['{}', '$', '^', '_', '\\'], '', $text);

        // Replace math symbols with words
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

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
