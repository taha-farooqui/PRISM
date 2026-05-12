<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\GeneratedVideo;
use App\Models\Lesson;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function show(Course $course, Lesson $lesson)
    {
        if ($course->user_id !== Auth::id()) {
            abort(403);
        }

        // Find or create a video for this lesson
        $video = GeneratedVideo::where('user_id', Auth::id())
            ->where('topic', $lesson->title)
            ->latest()
            ->first();

        // If no video exists, create one and start generation
        if (!$video) {
            $video = GeneratedVideo::create([
                'user_id' => Auth::id(),
                'topic' => $lesson->title,
                'quality' => 'l',
                'status' => 'processing',
            ]);

            Activity::create([
                'user_id' => Auth::id(),
                'type' => 'generating_video',
                'description' => "Auto-generating video for lesson: {$lesson->title}",
            ]);

            // Spawn background pipeline
            $artisan = base_path('artisan');
            $escapedTopic = escapeshellarg($lesson->title);
            $logFile = storage_path('logs/prism-video-' . $video->id . '.log');

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $cmd = sprintf(
                    'start /B php %s prism:generate-video %d %s > %s 2>&1',
                    escapeshellarg($artisan),
                    $video->id,
                    $escapedTopic,
                    escapeshellarg($logFile)
                );
                pclose(popen($cmd, 'r'));
            } else {
                $cmd = sprintf(
                    'php %s prism:generate-video %d %s > %s 2>&1 &',
                    escapeshellarg($artisan),
                    $video->id,
                    $escapedTopic,
                    escapeshellarg($logFile)
                );
                exec($cmd);
            }

            Log::info("[PRISM] Auto-started video generation for lesson: {$lesson->title}, video ID: {$video->id}");
        }

        // Ensure a Conversation exists linking this video so it appears in recent chats and is loadable via FK
        $conversation = Conversation::where('user_id', Auth::id())
            ->where('generated_video_id', $video->id)
            ->first();
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => "Video: {$lesson->title}",
                'mode' => 'generate_video',
                'generated_video_id' => $video->id,
            ]);
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => "Generate video: {$lesson->title}",
            ]);
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => "Started generating video for \"{$lesson->title}\". This may take a few minutes.",
            ]);
        }

        // If video failed, allow re-triggering
        if ($video->status === 'failed') {
            $video->update(['status' => 'processing', 'error_message' => null]);

            $artisan = base_path('artisan');
            $escapedTopic = escapeshellarg($lesson->title);
            $logFile = storage_path('logs/prism-video-' . $video->id . '.log');

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $cmd = sprintf(
                    'start /B php %s prism:generate-video %d %s > %s 2>&1',
                    escapeshellarg($artisan),
                    $video->id,
                    $escapedTopic,
                    escapeshellarg($logFile)
                );
                pclose(popen($cmd, 'r'));
            } else {
                $cmd = sprintf(
                    'php %s prism:generate-video %d %s > %s 2>&1 &',
                    escapeshellarg($artisan),
                    $video->id,
                    $escapedTopic,
                    escapeshellarg($logFile)
                );
                exec($cmd);
            }
        }

        $videoUrl = $video->video_path ? \Storage::url($video->video_path) : null;

        return view('lessons.show', compact('course', 'lesson', 'video', 'videoUrl'));
    }
}
