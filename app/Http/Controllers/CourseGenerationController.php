<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Week;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourseGenerationController extends Controller
{
    public function create()
    {
        return view('courses.create');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'course_name' => 'required|string|max:255',
            'resource' => 'nullable|file|mimes:pdf,doc,docx,txt,pptx|max:10240',
        ]);

        $courseName = $request->course_name;
        $resourceContent = null;

        // If a file was uploaded, store it and note the filename
        if ($request->hasFile('resource')) {
            $file = $request->file('resource');
            $path = $file->store('course_resources', 'public');
            $resourceContent = "User uploaded a file: " . $file->getClientOriginalName();
        }

        // Generate course structure using OpenRouter
        $courseStructure = $this->generateWithAI($courseName, $resourceContent);

        if (!$courseStructure) {
            return back()->with('error', 'Failed to generate course. Please try again.')->withInput();
        }

        // Create course in database
        $course = Course::create([
            'user_id' => Auth::id(),
            'title' => $courseName,
            'description' => $courseStructure['description'] ?? null,
            'status' => 'active',
        ]);

        // Create weeks and lessons
        foreach ($courseStructure['weeks'] as $weekIndex => $week) {
            $weekModel = Week::create([
                'course_id' => $course->id,
                'title' => $week['title'],
                'order' => $weekIndex + 1,
            ]);

            foreach ($week['lessons'] as $lessonIndex => $lesson) {
                Lesson::create([
                    'week_id' => $weekModel->id,
                    'course_id' => $course->id,
                    'title' => $lesson['title'],
                    'description' => $lesson['description'] ?? '',
                    'duration_minutes' => $lesson['duration_minutes'] ?? 12,
                    'order' => $lessonIndex + 1,
                    'is_completed' => false,
                ]);
            }
        }

        // Log activity
        Activity::create([
            'user_id' => Auth::id(),
            'type' => 'generated_course',
            'description' => "Generated New Course: $courseName",
        ]);

        return redirect()->route('courses.show', $course->id);
    }

    private function generateWithAI($courseName, $resourceContext = null)
    {
        $prompt = "Generate a detailed course structure for: \"$courseName\".

Create exactly 4 weeks. Each week should have 4 lessons.

For each lesson provide:
- A specific, descriptive title (not generic)
- A 2-3 sentence description of what the lesson covers
- Duration in minutes (between 8-20 minutes)

" . ($resourceContext ? "Additional context from user: $resourceContext\n\n" : "") . "

Respond ONLY with valid JSON, no markdown, no backticks, no preamble. Use this exact structure:
{
    \"description\": \"A brief 1-2 sentence course description\",
    \"weeks\": [
        {
            \"title\": \"Week 1\",
            \"lessons\": [
                {
                    \"title\": \"Lesson title here\",
                    \"description\": \"Lesson description here...\",
                    \"duration_minutes\": 12
                }
            ]
        }
    ]
}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
            ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemini-2.0-flash-001',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a curriculum designer. Respond only with valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 3000,
            ]);

            $content = $response->json('choices.0.message.content');

            // Clean response — remove markdown backticks if present
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $content = trim($content);

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('OpenRouter API Error: ' . $e->getMessage());
            return null;
        }
    }
}
