<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIGeneratorService
{
    public function generateQuiz(string $textContent, int $questionCount = 10): ?array
    {
        $systemPrompt = 'You are an expert quiz generator. Generate multiple-choice questions from the provided lecture content. '
            . 'Each question must have exactly 4 options (A, B, C, D) with one correct answer. '
            . 'Include a brief explanation for why the correct answer is right. '
            . 'Respond ONLY with valid JSON, no markdown, no backticks, no preamble.';

        $userPrompt = "Generate exactly {$questionCount} multiple-choice quiz questions from this lecture content:\n\n"
            . $textContent
            . "\n\nRespond with this exact JSON structure:\n"
            . '{"title": "Quiz: [main topic]", "description": "A brief description of what this quiz covers", "questions": [{"question": "The question text", "option_a": "First option", "option_b": "Second option", "option_c": "Third option", "option_d": "Fourth option", "correct_answer": "A", "explanation": "Why this is correct"}]}';

        return $this->callAI($systemPrompt, $userPrompt, 4000);
    }

    public function generateFlashcards(string $textContent, int $cardCount = 15): ?array
    {
        $systemPrompt = 'You are an expert flashcard generator. Create study flashcards from the provided lecture content. '
            . 'Each flashcard has a front (question, term, or concept) and back (answer, definition, or explanation). '
            . 'Make them clear, concise, and educational. '
            . 'Respond ONLY with valid JSON, no markdown, no backticks, no preamble.';

        $userPrompt = "Generate exactly {$cardCount} study flashcards from this lecture content:\n\n"
            . $textContent
            . "\n\nRespond with this exact JSON structure:\n"
            . '{"title": "Flashcards: [main topic]", "description": "A brief description of what these flashcards cover", "cards": [{"front": "Question or term", "back": "Answer or definition"}]}';

        return $this->callAI($systemPrompt, $userPrompt, 3000);
    }

    private function callAI(string $systemPrompt, string $userPrompt, int $maxTokens): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
            ])->timeout(90)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemini-2.0-flash-001',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => $maxTokens,
            ]);

            $content = $response->json('choices.0.message.content');

            if (!$content) {
                return null;
            }

            // Clean response — remove markdown backticks if present
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $content = trim($content);

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('AI Generator Error: ' . $e->getMessage());
            return null;
        }
    }
}
