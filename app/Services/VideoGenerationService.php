<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * VideoGenerationService
 *
 * Handles ALL LLM calls for the video pipeline:
 *   1. Script generation (3-section animation script)
 *   2. Manim code generation
 *   3. Layout review
 *   4. Auto-repair on render failure
 *
 * Uses OpenRouter API exclusively.
 */
class VideoGenerationService
{
    private string $apiKey;
    private string $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    private string $scriptModel = 'google/gemini-2.0-flash-001';
    private string $codeModel = 'anthropic/claude-sonnet-4';

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY', '');
    }

    // ─────────────────────────────────────────────────────────
    // PHASE 1: Generate Animation Script (3 sections)
    // ─────────────────────────────────────────────────────────

    public function generateScript(string $topic): ?array
    {
        $system = <<<'PROMPT'
You are PRISM, an expert educational animation script writer in the style of 3Blue1Brown.
You create EXACTLY 3-section video scripts that are SHORT, PUNCHY, and VISUAL-FIRST.

OUTPUT FORMAT: Valid JSON object (no markdown, no ```json blocks).

STRUCTURE (exactly 3 sections):
{
  "topic": "<topic name>",
  "sections": [
    {
      "id": 1,
      "title": "Introduction",
      "section_type": "introduction",
      "narration": "15-20 second narration (40-55 words). Hook the viewer with a question or surprising fact. Then state what they'll learn. Be conversational, not academic.",
      "blackboard_notes": ["Key term", "Main formula"],
      "visual_instructions": [
        "Animated reveal of the topic with a striking visual",
        "Show ONE main formula or definition",
        "NO walls of text -- the visual carries the meaning"
      ],
      "visual_mode": "2D"
    },
    {
      "id": 2,
      "title": "Concept Explanation",
      "section_type": "concept",
      "narration": "25-35 second narration (70-95 words). Walk through HOW the concept works using the visual. Reference what the viewer SEES, like 'notice how this side grows...'.",
      "blackboard_notes": ["Step 1", "Step 2", "Key insight"],
      "visual_instructions": [
        "Build the diagram progressively (layer by layer)",
        "Use color coding: BLUE for given, GREEN for result, YELLOW for emphasis",
        "Animate at least 4 distinct phases (transform/morph/move) -- not static"
      ],
      "visual_mode": "2D"
    },
    {
      "id": 3,
      "title": "Worked Example",
      "section_type": "examples",
      "narration": "25-35 second narration (70-95 words). Solve ONE concrete example step-by-step. End with the answer highlighted dramatically.",
      "blackboard_notes": ["Example: ...", "Step 1: ...", "Step 2: ...", "Answer: ..."],
      "visual_instructions": [
        "Show the problem with concrete numbers, not abstract variables",
        "Animate each step transforming into the next (TransformMatchingTex)",
        "Final answer in a YELLOW SurroundingRectangle with Indicate() flash"
      ],
      "visual_mode": "2D"
    }
  ]
}

RULES:
- EXACTLY 3 sections.
- Total video length MUST be UNDER 90 seconds. Short and impactful.
- Narration should reference what's being shown ("watch as this circle rotates...").
- Use 2D unless 3D is genuinely required (3D geometry, vectors in space).
- Conversational tone -- imagine talking to a friend, not lecturing a class.
- blackboard_notes: SHORT terms only (< 15 chars each). NOT full sentences.
- visual_instructions: action-focused, not description-focused.
- Section 3: ONE worked example only -- skip practice questions to keep video short.
- Output ONLY the JSON object. No explanation, no markdown.
PROMPT;

        $user = "Create a SHORT, ENGAGING 3-section video script for: \"{$topic}\"\n\n"
            . "Total video length should be UNDER 90 SECONDS. Punchy narration. Visual-first.\n"
            . "Output ONLY valid JSON. Exactly 3 sections.";

        $result = $this->callLLM($this->scriptModel, $system, $user, 0.7, 3000);

        if (!$result) {
            Log::warning("Script generation failed for topic: {$topic}, using fallback");
            return $this->fallbackScript($topic);
        }

        $parsed = $this->parseJson($result);

        if (!$parsed || empty($parsed['sections']) || count($parsed['sections']) !== 3) {
            Log::warning("Script parse failed, using fallback");
            return $this->fallbackScript($topic);
        }

        return $parsed;
    }

    // ─────────────────────────────────────────────────────────
    // PHASE 2: Generate Manim Code
    // ─────────────────────────────────────────────────────────

    public function generateManimCode(array $script, array $sectionDurations): ?string
    {
        $system = $this->getManimSystemPrompt();
        $user = $this->buildManimUserPrompt($script, $sectionDurations);

        $raw = $this->callLLM($this->codeModel, $system, $user, 0.2, 8192);

        if (!$raw) {
            return null;
        }

        $code = $this->extractCode($raw);
        return $code ? $this->postProcess($code) : null;
    }

    // ─────────────────────────────────────────────────────────
    // PHASE 3: Repair Failed Manim Code
    // ─────────────────────────────────────────────────────────

    public function repairManimCode(string $code, string $error): ?string
    {
        $system = $this->getManimSystemPrompt();

        $user = "The Manim code below failed to render. Fix it and return ONLY the "
            . "corrected Python code -- no explanation, no markdown.\n\n"
            . "Rules you MUST follow in the fix:\n"
            . "- Keep class GenScene(Scene) -- never ThreeDScene.\n"
            . "- Keep the same visual content and all 3 sections.\n"
            . "- Use Manim Community Edition syntax only.\n"
            . "- Every self.play() must have run_time= set explicitly.\n\n"
            . "ERROR:\n" . substr($error, -2000) . "\n\n"
            . "CODE:\n" . $code;

        $raw = $this->callLLM($this->codeModel, $system, $user, 0.2, 8192);

        if (!$raw) {
            return null;
        }

        $code = $this->extractCode($raw);
        return $code ? $this->postProcess($code) : null;
    }

    // ─────────────────────────────────────────────────────────
    // Manim System Prompt
    // ─────────────────────────────────────────────────────────

    private function getManimSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert Manim Community Edition developer creating ENGAGING educational videos
in the style of 3Blue1Brown -- where VISUALS DO THE TEACHING, not text walls.

The screen is 14.2 x 8 Manim units (x in [-7.1, 7.1], y in [-4, 4]).

==============  CORE PHILOSOPHY: SHOW, DON'T TELL  ==============

A great educational video is 80% animation, 20% labels.
The narration explains; the screen DEMONSTRATES.

WRONG approach (boring):
  - Wall of bullet points being typed slowly
  - Static text describing what something is
  - 5+ lines of explanation reading word by word

RIGHT approach (engaging):
  - Animated diagrams that EVOLVE while narration plays
  - Numbers/values changing in real time using ValueTracker
  - Shapes morphing, splitting, combining (Transform / ReplacementTransform)
  - Mathematical relationships shown by MOTION (e.g., a triangle rotating to show angle sum)
  - Color-coded elements that highlight as concepts are introduced
  - At MOST 1 short label per visual, NOT a paragraph

==============  ANIMATION STRATEGY (CRITICAL)  ==============

Each section should have AT LEAST 4-6 distinct ANIMATED transitions per 20 seconds.
NO long static waits. If a self.wait() exceeds 2 seconds, replace with a continuous
animation (Rotate, MoveAlongPath, AnimationGroup, Indicate, FocusOn, Wiggle, Flash).

PREFERRED ANIMATIONS (use these heavily):
  - Create / DrawBorderThenFill -- shapes appearing
  - Transform / ReplacementTransform / TransformMatchingTex -- equations evolving
  - ValueTracker + always_redraw -- numbers/positions updating live
  - MoveAlongPath / MoveToTarget -- objects flying to position
  - GrowFromCenter / SpinInFromNothing -- emphasis
  - Indicate / Flash / Wiggle -- highlighting key elements
  - FadeIn(shift=UP) -- subtle entry animation
  - Write -- ONLY for short labels (< 20 chars), never for long sentences

AVOID (these make videos boring):
  - Long Text() blocks rendered with Write() -- looks like reading a book
  - Multiple long self.wait() calls back-to-back
  - Static screens with no motion for more than 2 seconds
  - More than 4 text objects on screen at once

==============  LAYOUT (less rigid, more flexible)  ==============

Default split: text LEFT, visuals RIGHT.
BUT for visual-heavy moments, the visual can take CENTER STAGE (full width, no text).

Helper methods (paste into your class):

   def _fit_left(self, group):
       MAX_W, MAX_H = 5.5, 6.0
       if group.width > MAX_W:
           group.scale_to_fit_width(MAX_W)
       if group.height > MAX_H:
           group.scale_to_fit_height(MAX_H)
       group.move_to([-3.5, 0, 0])
       return group

   def _fit_right(self, group):
       MAX_W, MAX_H = 5.5, 6.0
       if group.width > MAX_W:
           group.scale_to_fit_width(MAX_W)
       if group.height > MAX_H:
           group.scale_to_fit_height(MAX_H)
       group.move_to([3.5, 0, 0])
       return group

   def _fit_center(self, group):
       MAX_W, MAX_H = 11.5, 6.5
       if group.width > MAX_W:
           group.scale_to_fit_width(MAX_W)
       if group.height > MAX_H:
           group.scale_to_fit_height(MAX_H)
       group.move_to(ORIGIN)
       return group

USE _fit_center() when there is no text on screen (e.g., during a big animated demo).

==============  TYPOGRAPHY (USE TEXT SPARINGLY)  ==============

Background : BLACK (default). Never change.
Section title : WHITE, .scale(0.85), shown briefly (2 sec max), then FadeOut.
Labels      : .scale(0.5) -- short, 1-3 words MAX per label.
Key terms   : YELLOW for emphasis.
Math        : MathTex in WHITE, .scale(0.7).
Final answer: SurroundingRectangle in YELLOW.
Shape colors: BLUE (primary), GREEN (success/correct), RED (warning), ORANGE (accent), PURPLE (#7C3AED).

TEXT BUDGET PER SECTION:
- Title: 1 line, < 30 chars
- Labels: max 3-4 short labels (each < 15 chars)
- Equations: max 2 MathTex objects
- NO paragraphs of body text -- the narration carries the verbal explanation

==============  SECTION STRUCTURE  ==============

Each section follows this RHYTHM (creates engagement):

1. HOOK (1-2 sec)
   - Show the section title centered, then FadeOut.
   - Or jump straight into a teaser visual.

2. BUILD (most of section)
   - Animate the concept progressively.
   - Show 3-4 distinct phases with transitions between them.
   - Use ValueTracker for live numbers when applicable.
   - Layer in elements one by one (not all at once).

3. PAYOFF (last 2-3 sec)
   - Highlight the final result with Indicate / Flash / SurroundingRectangle.
   - In Section 3 specifically, this is the worked-example answer.

==============  EXAMPLES OF GOOD ANIMATION PATTERNS  ==============

PATTERN A -- Live equation transformation:
   eq1 = MathTex(r"x^2 + 2x = 8")
   eq2 = MathTex(r"x^2 + 2x - 8 = 0")
   eq3 = MathTex(r"(x+4)(x-2) = 0")
   self.play(Write(eq1), run_time=0.8)
   self.play(TransformMatchingTex(eq1, eq2), run_time=1.0)
   self.play(TransformMatchingTex(eq2, eq3), run_time=1.0)
   self.play(Indicate(eq3, color=YELLOW), run_time=0.6)

PATTERN B -- Live value update:
   t = ValueTracker(0)
   value = always_redraw(lambda: DecimalNumber(t.get_value(), num_decimal_places=2).move_to([3, 0, 0]))
   self.add(value)
   self.play(t.animate.set_value(10), run_time=2)

PATTERN C -- Interactive geometry:
   triangle = Polygon([-1,-1,0], [1,-1,0], [0,1,0], color=BLUE)
   self.play(Create(triangle), run_time=0.8)
   self.play(triangle.animate.rotate(PI/4), run_time=0.8)
   self.play(triangle.animate.scale(1.5), run_time=0.8)
   self.play(triangle.animate.set_fill(BLUE, 0.4), run_time=0.5)

PATTERN D -- Side-by-side comparison:
   left_box = Square(color=BLUE).move_to([-3, 0, 0])
   right_box = Square(color=GREEN).move_to([3, 0, 0])
   self.play(Create(left_box), Create(right_box), run_time=0.8)
   self.play(left_box.animate.scale(2), right_box.animate.scale(0.5), run_time=1.5)

==============  DURATION MATCHING  ==============

Each section has a target duration. Distribute time as:
   60% on animations (run_time)
   40% on small waits between (max 1.5s each)

NEVER use a single self.wait() longer than 2 seconds.
If you have leftover time, ADD MORE ANIMATIONS (Indicate, Flash, color shifts on existing objects).

==============  CODE RULES  ==============

1.  Output ONLY valid Python. No markdown fences, no prose.
2.  First line: from manim import *
3.  Second line: import numpy as np
4.  Class name MUST be: class GenScene(Scene):
5.  Use Scene (2-D), NOT ThreeDScene.
6.  Do NOT call self.set_camera_orientation or add_fixed_in_frame_mobjects.
7.  Define helper methods _fit_left, _fit_right, _fit_center inside the class.
8.  For multi-part math, use a SINGLE string per MathTex call:
        MathTex(r"\frac{a}{b} + c = d")     # CORRECT
        MathTex(r"\frac{a}", "+", r"c")      # WRONG (breaks braces)
9.  Keep labels <= 15 chars. Body text on screen <= 40 chars per line.
10. Every self.play() call MUST have run_time= set explicitly.
11. self.wait() must never exceed 2 seconds. Prefer animations over waits.
12. To clear the screen between sections:
        if self.mobjects:
            self.play(*[FadeOut(m) for m in self.mobjects], run_time=0.4)
    NEVER call self.play() with an empty list.
13. AT LEAST 4 distinct self.play() calls per section. More = better.
14. Section 3 MUST end with the final answer wrapped in a YELLOW SurroundingRectangle
    AND Indicate() animation on it.

==============  REMEMBER  ==============

Your goal: make a student WATCH this video without checking their phone.
A 20-second section should have 5-8 visual transitions, not one slow text dump.
Move things. Transform things. Make numbers count up. Show, don't write.
PROMPT;
    }

    // ─────────────────────────────────────────────────────────
    // Build user prompt for Manim code generation
    // ─────────────────────────────────────────────────────────

    private function buildManimUserPrompt(array $script, array $durations): string
    {
        $sections = '';
        foreach ($script['sections'] as $i => $sec) {
            $dur = $durations[$i] ?? 20.0;
            $notes = is_array($sec['blackboard_notes'] ?? null)
                ? implode(', ', $sec['blackboard_notes'])
                : ($sec['blackboard_notes'] ?? '');
            $visuals = is_array($sec['visual_instructions'] ?? null)
                ? implode('; ', $sec['visual_instructions'])
                : ($sec['visual_instructions'] ?? '');

            $sections .= "--- SECTION {$sec['id']}: {$sec['title']} ---\n"
                . "Duration: {$dur}s\n"
                . "Narration: \"{$sec['narration']}\"\n"
                . "Notes: {$notes}\n"
                . "Visuals: {$visuals}\n\n";
        }

        $totalDuration = array_sum($durations);

        return "Topic: \"{$script['topic']}\"\n\n"
            . "SECTIONS:\n{$sections}"
            . "Total duration: ~{$totalDuration}s\n\n"
            . "REQUIREMENTS:\n"
            . "1. The last section MUST have at least ONE fully worked example with step-by-step solution.\n"
            . "2. Highlight the final answer with a YELLOW SurroundingRectangle.\n"
            . "3. FadeOut ALL objects before each new section.\n"
            . "4. Match each section's target duration with self.wait() and run_time.\n\n"
            . "OUTPUT: Python code only.";
    }

    // ─────────────────────────────────────────────────────────
    // Code extraction and post-processing
    // ─────────────────────────────────────────────────────────

    private function extractCode(string $raw): ?string
    {
        $text = trim($raw);

        // Strip markdown fences
        $text = preg_replace('/^```(?:python)?\s*\n?/m', '', $text);
        $text = preg_replace('/\n?```\s*$/m', '', $text);
        $text = trim($text);

        if (empty($text)) {
            return null;
        }

        // Ensure manim import present
        if (strpos($text, 'from manim import') === false) {
            $text = "from manim import *\nimport numpy as np\n\n" . $text;
        }

        return $text;
    }

    private function postProcess(string $code): string
    {
        // Fix Dot(ORIGIN) -> Dot(point=ORIGIN)
        $code = preg_replace(
            '/Dot\((?!point=)((?:ORIGIN|UP|DOWN|LEFT|RIGHT|np\.array|\[))/',
            'Dot(point=$1',
            $code
        );

        // Ensure class name is GenScene(Scene) -- NOT ThreeDScene
        $code = preg_replace(
            '/class\s+GenScene\s*\(\s*ThreeDScene\s*\)/',
            'class GenScene(Scene)',
            $code
        );

        // Guard bare self.play(*[FadeOut(m) for m in self.mobjects]) against empty list
        // Replace with: if self.mobjects:\n            self.play(...)
        $code = preg_replace(
            '/^(\s*)self\.play\(\*\[FadeOut\(m\)\s+for\s+m\s+in\s+self\.mobjects\](.*?)\)/m',
            "$1if self.mobjects:\n$1    self.play(*[FadeOut(m) for m in self.mobjects]$2)",
            $code
        );

        return $code;
    }

    // ─────────────────────────────────────────────────────────
    // Fallback script when LLM fails
    // ─────────────────────────────────────────────────────────

    private function fallbackScript(string $topic): array
    {
        return [
            'topic' => $topic,
            'sections' => [
                [
                    'id' => 1,
                    'title' => 'Introduction',
                    'section_type' => 'introduction',
                    'narration' => "Welcome to this lesson on {$topic}. Today we will explore the key concepts, understand the main formula, and see how it applies to real problems. Let's get started with the basics.",
                    'blackboard_notes' => [$topic, 'Key Definition'],
                    'visual_instructions' => ['Show title text', 'Display main formula'],
                    'visual_mode' => '2D',
                ],
                [
                    'id' => 2,
                    'title' => 'Concept Explanation',
                    'section_type' => 'concept',
                    'narration' => "Now let's break down {$topic} step by step. We start by understanding each component of the formula. Each part plays an important role in how this concept works. Pay attention to how they connect together.",
                    'blackboard_notes' => ['Step 1', 'Step 2', 'Key insight'],
                    'visual_instructions' => ['Draw labeled diagram', 'Animate step by step'],
                    'visual_mode' => '2D',
                ],
                [
                    'id' => 3,
                    'title' => 'Worked Examples & Practice',
                    'section_type' => 'examples',
                    'narration' => "Let's work through an example. We apply the formula step by step to get our answer. Practice question one: try solving a similar problem. Practice question two: apply the concept in a different context. The answers are shown on screen.",
                    'blackboard_notes' => ['Example', 'Step 1', 'Answer', 'Practice Q1: ... Answer: ...', 'Practice Q2: ... Answer: ...'],
                    'visual_instructions' => ['Show example problem', 'Animate solution steps'],
                    'visual_mode' => '2D',
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────
    // LLM Call (OpenRouter)
    // ─────────────────────────────────────────────────────────

    private function callLLM(string $model, string $system, string $user, float $temperature, int $maxTokens): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error("LLM call failed [{$model}]: " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────
    // JSON parsing with fallbacks
    // ─────────────────────────────────────────────────────────

    private function parseJson(string $raw): ?array
    {
        $text = trim($raw);

        // Strip markdown fences
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        // Try direct parse
        $parsed = json_decode($text, true);
        if ($parsed) {
            return $parsed;
        }

        // Try extracting JSON object
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) {
                return $parsed;
            }
        }

        return null;
    }
}
