<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\GeneratedVideo;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        $topic = $request->input('topic');

        // Create DB record
        $video = GeneratedVideo::create([
            'user_id' => Auth::id(),
            'topic' => $topic,
            'quality' => 'l',
            'status' => 'processing',
        ]);

        Activity::create([
            'user_id' => Auth::id(),
            'type' => 'generating_video',
            'description' => "Started generating video: {$topic}",
        ]);

        // Create a conversation entry so it appears in recent chats
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => "Video: {$topic}",
            'mode' => 'generate_video',
            'generated_video_id' => $video->id,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => "Generate video: {$topic}",
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => "Started generating video for \"{$topic}\". This may take a few minutes.",
        ]);

        // Spawn the pipeline as a background artisan command (no PHP timeout)
        $artisan = base_path('artisan');
        $escapedTopic = escapeshellarg($topic);
        $cmd = sprintf(
            'php %s prism:generate-video %d %s > %s 2>&1 &',
            escapeshellarg($artisan),
            $video->id,
            $escapedTopic,
            escapeshellarg(storage_path('logs/prism-video-' . $video->id . '.log'))
        );

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // On Windows, use 'start /B' for background execution
            $cmd = sprintf(
                'start /B php %s prism:generate-video %d %s > %s 2>&1',
                escapeshellarg($artisan),
                $video->id,
                $escapedTopic,
                escapeshellarg(storage_path('logs/prism-video-' . $video->id . '.log'))
            );
            pclose(popen($cmd, 'r'));
        } else {
            exec($cmd);
        }

        Log::info("[PRISM] Dispatched background pipeline for video {$video->id}: {$topic}");

        return response()->json([
            'video_id' => $video->id,
            'status' => 'processing',
            'topic' => $topic,
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
        ]);
    }

    public function status(GeneratedVideo $video)
    {
        if ($video->user_id !== Auth::id()) {
            abort(403);
        }

        $video = $video->fresh();

        $response = [
            'status' => $video->status,
            'progress_phase' => $video->progress_phase,
            'progress_percent' => (int) $video->progress_percent,
        ];

        if ($video->status === 'completed' && $video->video_path) {
            $response['video_url'] = Storage::url($video->video_path);
            $response['progress_percent'] = 100;
        } elseif ($video->status === 'failed') {
            $response['error'] = $video->error_message;
        } elseif ($video->status === 'processing' && $video->created_at) {
            $response['elapsed_seconds'] = round(now()->diffInSeconds($video->created_at), 1);
        }

        return response()->json($response);
    }

    public function index()
    {
        $videos = Auth::user()->generatedVideos()->get();

        return view('videos.index', compact('videos'));
    }

    public function show(GeneratedVideo $video)
    {
        if ($video->user_id !== Auth::id()) {
            abort(403);
        }

        $videoUrl = $video->video_path ? Storage::url($video->video_path) : null;

        return view('videos.show', compact('video', 'videoUrl'));
    }

    public function destroy(GeneratedVideo $video)
    {
        if ($video->user_id !== Auth::id()) {
            abort(403);
        }

        if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
            Storage::disk('public')->delete($video->video_path);
        }

        $video->delete();

        return redirect()->route('videos.index')->with('success', 'Video deleted.');
    }
}
