<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\GeneratedVideo;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    public function show()
    {
        $userId = Auth::id();

        $stats = [
            'courses' => Course::where('user_id', $userId)->count(),
            'quizzes' => Quiz::where('user_id', $userId)->count(),
            'videos' => GeneratedVideo::where('user_id', $userId)->count(),
            'flashcard_sets' => FlashcardSet::where('user_id', $userId)->count(),
        ];

        $activities = Activity::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $attempts = QuizAttempt::where('user_id', $userId)
            ->where('total_questions', '>', 0)
            ->get();

        $averageScore = 0;
        if ($attempts->count() > 0) {
            $sum = 0;
            foreach ($attempts as $attempt) {
                $sum += ($attempt->score / $attempt->total_questions) * 100;
            }
            $averageScore = (int) round($sum / $attempts->count());
        }

        return view('progress.show', compact('stats', 'activities', 'averageScore'));
    }
}
