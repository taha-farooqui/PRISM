<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ContentSafetyService
 *
 * Two-layer content filter for video generation topics:
 *   1. Fast keyword blocklist (no API call)
 *   2. LLM-based classifier (Gemini Flash) for ambiguous cases
 *
 * Returns ['allowed' => bool, 'reason' => string|null].
 */
class ContentSafetyService
{
    private string $apiKey;
    private string $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    private string $model = 'google/gemini-2.0-flash-001';

    /**
     * Categories the platform refuses to generate educational videos about.
     * Keep this list narrow and student-safety focused.
     */
    private const BLOCKED_CATEGORIES = [
        'sexual / adult content',
        'graphic violence or gore',
        'self-harm or suicide methods',
        'illegal drug synthesis / drug abuse instruction',
        'weapons creation (firearms, explosives, bombs)',
        'hate speech against protected groups',
        'instructions for crimes (hacking specific people, fraud, theft)',
        'CSAM / minors in sexual context',
    ];

    /**
     * Fast-path keyword blocklist. Triggers an immediate reject without LLM call.
     * Substring match (case-insensitive). Keep this list tight to avoid false positives.
     */
    private const BLOCKLIST_KEYWORDS = [
        // explicit sexual
        'sex', 'porn', 'porno', 'nude', 'naked', 'nsfw', 'xxx',
        'masturbat', 'orgasm', 'erotic', 'fetish', 'incest', 'pedo',
        'penis', 'vagina', 'breast feeding adult', 'blowjob', 'handjob',
        'anal', 'hentai', 'rule 34',
        // graphic violence
        'gore', 'behead', 'decapitat', 'torture method',
        // self-harm
        'how to suicide', 'kill myself', 'suicide method', 'ways to die',
        // drug synthesis (not general education)
        'how to make meth', 'cook meth', 'synthesize cocaine', 'make heroin',
        // weapons creation
        'make a bomb', 'build a bomb', 'how to bomb', 'pipe bomb',
        'homemade gun', '3d print gun', 'ghost gun',
        // hate slurs are intentionally NOT keyword-matched here (too many false positives
        // on legitimate educational content); LLM layer handles them.
    ];

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY', '');
    }

    /**
     * Check whether a topic is safe for video generation.
     *
     * @param  string $topic User-supplied topic prompt.
     * @return array  ['allowed' => bool, 'reason' => string|null, 'layer' => 'keyword'|'llm'|'pass']
     */
    public function check(string $topic): array
    {
        $topic = trim($topic);

        if ($topic === '') {
            return ['allowed' => false, 'reason' => 'Topic cannot be empty.', 'layer' => 'validation'];
        }

        // Layer 1: keyword blocklist (cheap & instant)
        $hit = $this->keywordHit($topic);
        if ($hit !== null) {
            Log::warning("[ContentSafety] Blocked by keyword: '{$hit}' in topic: " . substr($topic, 0, 120));
            return [
                'allowed' => false,
                'reason' => 'This topic appears to contain content that is not suitable for educational video generation. Please choose an academic topic.',
                'layer' => 'keyword',
            ];
        }

        // Layer 2: LLM classifier (handles edge cases the keyword list misses)
        $llmResult = $this->llmClassify($topic);
        if (!$llmResult['allowed']) {
            Log::warning("[ContentSafety] Blocked by LLM: {$llmResult['reason']} | topic: " . substr($topic, 0, 120));
            return [
                'allowed' => false,
                'reason' => $llmResult['reason'] ?? 'This topic is not suitable for educational video generation.',
                'layer' => 'llm',
            ];
        }

        return ['allowed' => true, 'reason' => null, 'layer' => 'pass'];
    }

    /**
     * Layer 1: keyword scan with word-boundary matching to avoid Scunthorpe-style
     * false positives (e.g. "gore" must not match "pythaGOREan").
     *
     * For keywords containing spaces or numbers, falls back to a case-insensitive
     * substring match (still safer than no check at all).
     */
    private function keywordHit(string $topic): ?string
    {
        $lower = mb_strtolower($topic);

        foreach (self::BLOCKLIST_KEYWORDS as $kw) {
            $kwLower = mb_strtolower($kw);

            // Multi-word phrases: use plain substring search
            if (str_contains($kw, ' ')) {
                if (str_contains($lower, $kwLower)) {
                    return $kw;
                }
                continue;
            }

            // Single-word: require word boundaries so "gore" doesn't match "pythagorean"
            $pattern = '/(?<![a-z0-9])' . preg_quote($kwLower, '/') . '(?![a-z0-9])/iu';
            if (preg_match($pattern, $lower)) {
                return $kw;
            }
        }
        return null;
    }

    /**
     * Layer 2: LLM-based classifier. Returns ['allowed' => bool, 'reason' => string].
     * On any API failure, defaults to ALLOWED (fail-open) so that genuine educational
     * requests are not blocked by infrastructure issues.
     */
    private function llmClassify(string $topic): array
    {
        if (empty($this->apiKey)) {
            return ['allowed' => true, 'reason' => 'no-api-key'];
        }

        $categories = implode(', ', self::BLOCKED_CATEGORIES);

        $system = "You are a content safety classifier for an educational video platform "
            . "used by university students.\n\n"
            . "Block topics that fall into any of these categories:\n"
            . "  {$categories}\n\n"
            . "ALLOW (do NOT block) academic discussions of these subjects when the framing "
            . "is educational. Examples of ALLOWED topics:\n"
            . "  - Human reproduction biology, sexual health education\n"
            . "  - Historical wars, the Holocaust, atomic bomb history\n"
            . "  - Pharmacology, anatomy, forensic science\n"
            . "  - Cybersecurity concepts, ethical hacking principles\n"
            . "  - World religions, political systems, philosophy\n\n"
            . "Block ONLY if the request is clearly asking for explicit / pornographic / "
            . "how-to-harm content rather than educational coverage.\n\n"
            . "Respond with EXACTLY one JSON object, no markdown:\n"
            . '  {"allowed": true} '
            . "if the topic is appropriate for a 90-second educational explainer video, OR\n"
            . '  {"allowed": false, "reason": "<one short student-facing sentence>"} '
            . "if it is NOT appropriate.\n\n"
            . "Be lenient — when in doubt, allow.";

        $user = "Topic submitted by student: \"{$topic}\"\n\n"
            . "Classify. Respond with ONLY the JSON.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
            ])->timeout(15)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.0,
                'max_tokens' => 100,
            ]);

            $content = $response->json('choices.0.message.content');
            if (empty($content)) {
                return ['allowed' => true, 'reason' => 'empty-llm-response'];
            }

            // Strip markdown fences if any
            $content = trim($content);
            $content = preg_replace('/^```(?:json)?\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            // Try to extract just the JSON object
            if (preg_match('/\{[^}]*\}/s', $content, $m)) {
                $content = $m[0];
            }

            $parsed = json_decode($content, true);
            if (!is_array($parsed)) {
                return ['allowed' => true, 'reason' => 'unparseable-llm-response'];
            }

            $allowed = (bool) ($parsed['allowed'] ?? true);
            $reason = $parsed['reason'] ?? null;

            return [
                'allowed' => $allowed,
                'reason' => $reason ?: 'This topic is not suitable for our educational platform.',
            ];
        } catch (\Throwable $e) {
            Log::error("[ContentSafety] LLM classifier failed: " . $e->getMessage());
            return ['allowed' => true, 'reason' => 'api-error']; // fail-open
        }
    }
}
