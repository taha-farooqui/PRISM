<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Course;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display the my courses page.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'active');

        $query = Course::where('user_id', Auth::id())
            ->withCount(['classes as total_classes'])
            ->withCount(['classes as completed_classes' => function ($q) {
                $q->where('is_completed', true);
            }])
            ->orderBy('updated_at', 'desc');

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $courses = $query->get()->map(function ($course) {
            $course->progress = $course->total_classes > 0
                ? round(($course->completed_classes / $course->total_classes) * 100)
                : 0;
            $course->classes_left = $course->total_classes - $course->completed_classes;
            return $course;
        });

        // Recent activity for the sidebar widgets
        $recentActivity = Activity::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Continue learning - get the last accessed course/lecture
        $continueLearning = UserProgress::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->with(['course', 'currentClass'])
            ->first();

        // Streak calculation
        $streak = $this->calculateStreak(Auth::id());

        // Recent chats for sidebar
        $recentChats = [
            ['id' => 1, 'title' => 'Os Fundamentals'],
            ['id' => 2, 'title' => 'Business Plans Fundamentals'],
            ['id' => 3, 'title' => 'Bubble Sort Explanation'],
            ['id' => 4, 'title' => 'Heap Sort Practice Questions'],
            ['id' => 5, 'title' => 'Srs Sds Documentation Explanation'],
        ];

        return view('my-courses', compact(
            'courses',
            'recentActivity',
            'continueLearning',
            'streak',
            'recentChats',
            'filter'
        ));
    }

    /**
     * Display a single course with its weeks and lessons.
     */
    public function show(Course $course)
    {
        if ($course->user_id !== Auth::id()) {
            abort(403);
        }

        $course->load(['weeks.lessons']);

        // Recent chats for sidebar
        $recentChats = [
            ['id' => 1, 'title' => 'Os Fundamentals'],
            ['id' => 2, 'title' => 'Business Plans Fundamentals'],
            ['id' => 3, 'title' => 'Bubble Sort Explanation'],
        ];

        return view('courses.show', compact('course', 'recentChats'));
    }

    /**
     * Delete a course.
     */
    public function destroy(Course $course)
    {
        if ($course->user_id !== Auth::id()) {
            abort(403);
        }

        $course->delete();

        return redirect()->back()->with('success', 'Course deleted successfully.');
    }

    /**
     * Calculate user's learning streak.
     */
    private function calculateStreak($userId)
    {
        $today = now()->startOfDay();
        $streak = 0;
        $checkDate = $today;

        while (true) {
            $hasActivity = Activity::where('user_id', $userId)
                ->whereDate('created_at', $checkDate)
                ->exists();

            if ($hasActivity) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
