<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Message;
use App\Services\AIGeneratorService;
use App\Services\TextExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlashcardController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,txt,pptx|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('flashcard_resources', 'public');
        $fullPath = storage_path('app/public/' . $path);

        // Extract text
        $extractor = new TextExtractorService();
        $text = $extractor->extractText($fullPath);

        if (strlen($text) < 100) {
            return response()->json(['error' => 'Could not extract enough text from the file. Please upload a text-based document.'], 422);
        }

        // Generate flashcards via AI
        $generator = new AIGeneratorService();
        $result = $generator->generateFlashcards($text);

        if (!$result || empty($result['cards'])) {
            return response()->json(['error' => 'Failed to generate flashcards. Please try again.'], 500);
        }

        // Save to database
        $set = DB::transaction(function () use ($result, $file) {
            $set = FlashcardSet::create([
                'user_id' => Auth::id(),
                'title' => $result['title'] ?? 'Flashcards',
                'description' => $result['description'] ?? null,
                'source_filename' => $file->getClientOriginalName(),
                'total_cards' => count($result['cards']),
            ]);

            foreach ($result['cards'] as $index => $card) {
                Flashcard::create([
                    'flashcard_set_id' => $set->id,
                    'front' => $card['front'],
                    'back' => $card['back'],
                    'order' => $index + 1,
                ]);
            }

            return $set;
        });

        Activity::create([
            'user_id' => Auth::id(),
            'type' => 'generated_flashcards',
            'description' => "Generated Flashcards: {$set->title}",
        ]);

        // Create a conversation entry so it appears in recent chats
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => "Flashcards: {$set->title}",
            'mode' => 'generate_flashcards',
            'flashcard_set_id' => $set->id,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => "Generate flashcards from: {$file->getClientOriginalName()}",
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => "Generated flashcard set \"{$set->title}\" with {$set->total_cards} cards.",
        ]);

        $cards = $set->flashcards()->get(['id', 'front', 'back', 'order']);

        return response()->json([
            'set_id' => $set->id,
            'title' => $set->title,
            'description' => $set->description,
            'total_cards' => $set->total_cards,
            'cards' => $cards,
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
        ]);
    }

    public function index()
    {
        $flashcardSets = Auth::user()->flashcardSets()->withCount('flashcards')->get();

        return view('flashcards.index', compact('flashcardSets'));
    }

    public function show(FlashcardSet $flashcardSet)
    {
        if ($flashcardSet->user_id !== Auth::id()) {
            abort(403);
        }

        $cards = $flashcardSet->flashcards()->get();

        return view('flashcards.show', compact('flashcardSet', 'cards'));
    }

    public function destroy(FlashcardSet $flashcardSet)
    {
        if ($flashcardSet->user_id !== Auth::id()) {
            abort(403);
        }

        $flashcardSet->delete();

        return redirect()->route('flashcards.index')->with('success', 'Flashcard set deleted.');
    }
}
