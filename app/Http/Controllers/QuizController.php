<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\AIGeneratorService;
use App\Services\TextExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,txt,pptx|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('quiz_resources', 'public');
        $fullPath = storage_path('app/public/' . $path);

        // Extract text
        $extractor = new TextExtractorService();
        $text = $extractor->extractText($fullPath);

        if (strlen($text) < 100) {
            return response()->json(['error' => 'Could not extract enough text from the file. Please upload a text-based document.'], 422);
        }

        // Generate quiz via AI
        $generator = new AIGeneratorService();
        $result = $generator->generateQuiz($text);

        if (!$result || empty($result['questions'])) {
            return response()->json(['error' => 'Failed to generate quiz. Please try again.'], 500);
        }

        // Save to database
        $quiz = DB::transaction(function () use ($result, $file) {
            $quiz = Quiz::create([
                'user_id' => Auth::id(),
                'title' => $result['title'] ?? 'Quiz',
                'description' => $result['description'] ?? null,
                'source_filename' => $file->getClientOriginalName(),
                'total_questions' => count($result['questions']),
            ]);

            foreach ($result['questions'] as $index => $q) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $q['question'],
                    'option_a' => $q['option_a'] ?? '',
                    'option_b' => $q['option_b'] ?? '',
                    'option_c' => $q['option_c'] ?? '',
                    'option_d' => $q['option_d'] ?? '',
                    'correct_answer' => strtoupper($q['correct_answer'] ?? 'A'),
                    'explanation' => $q['explanation'] ?? null,
                    'order' => $index + 1,
                ]);
            }

            return $quiz;
        });

        Activity::create([
            'user_id' => Auth::id(),
            'type' => 'generated_quiz',
            'description' => "Generated Quiz: {$quiz->title}",
        ]);

        // Create a conversation entry so it appears in recent chats
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => "Quiz: {$quiz->title}",
            'mode' => 'generate_quiz',
            'quiz_id' => $quiz->id,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => "Generate quiz from: {$file->getClientOriginalName()}",
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => "Generated quiz \"{$quiz->title}\" with {$quiz->total_questions} questions.",
        ]);

        return response()->json([
            'quiz_id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'total_questions' => $quiz->total_questions,
            'questions_preview' => $quiz->questions()->take(3)->get(['question'])->pluck('question'),
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
        ]);
    }

    public function index()
    {
        $quizzes = Auth::user()->quizzes()->withCount('questions')->with(['attempts' => function ($q) {
            $q->where('user_id', Auth::id())->orderBy('score', 'desc')->take(1);
        }])->get();

        return view('quizzes.index', compact('quizzes'));
    }

    public function show(Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403);
        }

        $questions = $quiz->questions()->get();

        return view('quizzes.show', compact('quiz', 'questions'));
    }

    public function submit(Request $request, Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'answers' => 'required|array',
        ]);

        $answers = $request->input('answers');
        $questions = $quiz->questions()->get();
        $score = 0;

        foreach ($questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;
            if ($userAnswer && strtoupper($userAnswer) === strtoupper($question->correct_answer)) {
                $score++;
            }
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => Auth::id(),
            'score' => $score,
            'total_questions' => $questions->count(),
            'answers' => $answers,
            'completed_at' => now(),
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'score' => $score,
            'total' => $questions->count(),
            'percentage' => round(($score / $questions->count()) * 100),
        ]);
    }

    public function result(QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $quiz = $attempt->quiz;
        $questions = $quiz->questions()->get();

        return view('quizzes.result', compact('attempt', 'quiz', 'questions'));
    }

    public function destroy(Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403);
        }

        $quiz->delete();

        return redirect()->route('quizzes.index')->with('success', 'Quiz deleted.');
    }
}
