<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\FlashcardSet;
use App\Models\GeneratedVideo;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'mode' => 'nullable|string',
            'resource_ids' => 'nullable|array',
            'resource_ids.*' => 'integer|exists:resources,id',
        ]);

        $user = Auth::user();
        $userMessage = $request->input('message');
        $resourceIds = $request->input('resource_ids', []);

        // Get or create conversation
        if ($request->conversation_id) {
            $conversation = Conversation::findOrFail($request->conversation_id);
            if ($conversation->user_id !== $user->id) {
                abort(403);
            }
        } else {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => Str::limit($userMessage, 50),
                'mode' => $request->input('mode', 'ask_any_topic'),
            ]);
        }

        // Save user message
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Build messages array for OpenRouter (last 20 messages for context)
        $contextMessages = $conversation->messages()
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $systemPrompt = "You are Prism AI, an intelligent learning assistant.\n\n"
            . "ABSOLUTE RULES:\n"
            . "1. Read ONLY the LAST user message and respond to THAT message specifically. Previous messages are CONTEXT, not the request.\n"
            . "2. NEVER repeat or paste back content from your previous responses. Every reply must be freshly generated for the current question.\n"
            . "3. If the user greets you (hi, hello, hey, salam, kia hal hai, kia hogya, etc.), greet back warmly and ask what they need help with. DO NOT show code or any prior content.\n"
            . "4. If the user asks 'explain in detail', 'elaborate', 'more details', or similar, give a THOROUGH multi-paragraph explanation with examples, bullet points, and step-by-step breakdowns. Aim for at least 200-400 words.\n"
            . "5. If the user asks a short factual question, give a concise but complete answer (3-5 sentences minimum, not a single line).\n"
            . "6. If the user asks for code, write clean code in a fenced code block with the language tag (```python, ```javascript, etc.).\n"
            . "7. If the user writes in Urdu, Hindi, Roman Urdu, or any non-English language, respond in that same language naturally.\n\n"
            . "FORMATTING:\n"
            . "- Use Markdown: **bold**, *italic*, bullet lists, numbered lists, GFM tables (pipe syntax).\n"
            . "- For math, use LaTeX: \$...\$ for inline and \$\$...\$\$ for display.\n"
            . "- Code blocks MUST have a language tag.\n"
            . "- Use headings (##, ###) to organize long answers.\n\n"
            . "TONE: Friendly, encouraging, conversational — like a smart tutor friend, not a textbook.";

        // Append attached resource context (if any)
        if (!empty($resourceIds)) {
            $resources = Resource::whereIn('id', $resourceIds)
                ->where('user_id', $user->id)
                ->get();

            if ($resources->count() > 0) {
                $systemPrompt .= "\n\nUser has attached the following documents:\n";
                foreach ($resources as $r) {
                    $excerpt = mb_substr((string) $r->extracted_text, 0, 3000);
                    $systemPrompt .= "[" . $r->original_filename . "]\n" . $excerpt . "\n---\n";
                }
            }
        }

        // Mark the latest user message explicitly so the model focuses on it,
        // not on long prior assistant responses still sitting in context.
        if (!empty($contextMessages)) {
            $lastIdx = count($contextMessages) - 1;
            if ($contextMessages[$lastIdx]['role'] === 'user') {
                $contextMessages[$lastIdx]['content'] =
                    "[THIS IS THE CURRENT QUESTION YOU MUST ANSWER]\n"
                    . $contextMessages[$lastIdx]['content']
                    . "\n[Respond ONLY to the question above. Do NOT repeat any previous answer.]";
            }
        }

        $apiMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $contextMessages
        );

        // Call OpenRouter API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
            ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemini-2.0-flash-001',
                'messages' => $apiMessages,
                'temperature' => 0.7,
                'max_tokens' => 4000,
            ]);

            $aiContent = $response->json('choices.0.message.content');

            if (!$aiContent) {
                return response()->json(['error' => 'Failed to get a response. Please try again.'], 500);
            }

            // Save assistant message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiContent,
            ]);

            // Touch conversation to update sidebar ordering
            $conversation->touch();

            return response()->json([
                'conversation_id' => $conversation->id,
                'title' => $conversation->title,
                'message' => [
                    'role' => 'assistant',
                    'content' => $aiContent,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Chat API Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function show(Conversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $response = [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'mode' => $conversation->mode,
            ],
            'messages' => $conversation->messages->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ]),
        ];

        if ($conversation->generated_video_id) {
            $video = GeneratedVideo::find($conversation->generated_video_id);
            if ($video) {
                $response['video'] = [
                    'video_id' => $video->id,
                    'topic' => $video->topic,
                    'status' => $video->status,
                    'video_url' => $video->video_path ? Storage::url($video->video_path) : null,
                    'subtitle_url' => $video->subtitle_path ? Storage::url($video->subtitle_path) : null,
                    'error' => $video->error_message,
                ];
            }
        }

        if ($conversation->quiz_id) {
            $quiz = Quiz::find($conversation->quiz_id);
            if ($quiz) {
                $response['quiz'] = [
                    'quiz_id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'total_questions' => $quiz->total_questions,
                    'questions_preview' => $quiz->questions()->take(3)->pluck('question'),
                ];
            }
        }

        if ($conversation->flashcard_set_id) {
            $set = FlashcardSet::find($conversation->flashcard_set_id);
            if ($set) {
                $response['flashcard_set'] = [
                    'set_id' => $set->id,
                    'title' => $set->title,
                    'description' => $set->description,
                    'total_cards' => $set->total_cards,
                    'cards' => $set->flashcards()->orderBy('order')->get(['front', 'back'])->values(),
                ];
            }
        }

        return response()->json($response);
    }

    public function regenerate(Conversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        // Delete the last assistant message
        $lastAssistant = $conversation->messages()->where('role', 'assistant')->latest()->first();
        if ($lastAssistant) {
            $lastAssistant->delete();
        }

        // Rebuild context
        $contextMessages = $conversation->messages()
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $systemPrompt = "You are Prism AI, an intelligent learning assistant.\n\n"
            . "ABSOLUTE RULES:\n"
            . "1. Read ONLY the LAST user message and respond to THAT message specifically. Previous messages are CONTEXT, not the request.\n"
            . "2. NEVER repeat or paste back content from your previous responses. Every reply must be freshly generated for the current question.\n"
            . "3. If the user greets you (hi, hello, hey, salam, kia hal hai, kia hogya, etc.), greet back warmly and ask what they need help with. DO NOT show code or any prior content.\n"
            . "4. If the user asks 'explain in detail', 'elaborate', 'more details', or similar, give a THOROUGH multi-paragraph explanation with examples, bullet points, and step-by-step breakdowns. Aim for at least 200-400 words.\n"
            . "5. If the user asks a short factual question, give a concise but complete answer (3-5 sentences minimum, not a single line).\n"
            . "6. If the user asks for code, write clean code in a fenced code block with the language tag (```python, ```javascript, etc.).\n"
            . "7. If the user writes in Urdu, Hindi, Roman Urdu, or any non-English language, respond in that same language naturally.\n\n"
            . "FORMATTING:\n"
            . "- Use Markdown: **bold**, *italic*, bullet lists, numbered lists, GFM tables (pipe syntax).\n"
            . "- For math, use LaTeX: \$...\$ for inline and \$\$...\$\$ for display.\n"
            . "- Code blocks MUST have a language tag.\n"
            . "- Use headings (##, ###) to organize long answers.\n\n"
            . "TONE: Friendly, encouraging, conversational — like a smart tutor friend, not a textbook.";

        $apiMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $contextMessages
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
            ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemini-2.0-flash-001',
                'messages' => $apiMessages,
                'temperature' => 0.8,
                'max_tokens' => 4000,
            ]);

            $aiContent = $response->json('choices.0.message.content');

            if (!$aiContent) {
                return response()->json(['error' => 'Failed to regenerate response.'], 500);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiContent,
            ]);

            $conversation->touch();

            return response()->json([
                'message' => [
                    'role' => 'assistant',
                    'content' => $aiContent,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Chat Regenerate Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
