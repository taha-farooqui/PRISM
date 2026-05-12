@extends('layouts.app')

@section('title', $video->topic . ' - Prism AI')

@section('content')
<div class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('videos.index') }}" class="text-sm text-gray-400 hover:text-purple-600 transition-colors mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Videos
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $video->topic }}</h1>
            <p class="text-sm text-gray-500 mt-1">Generated {{ $video->created_at->diffForHumans() }}</p>
        </div>

        @if($video->status === 'completed' && $videoUrl)
            <!-- Video Player -->
            <div class="bg-black rounded-2xl overflow-hidden shadow-lg mb-6">
                <video src="{{ $videoUrl }}" controls autoplay class="w-full" style="max-height: 500px;">
                    Your browser does not support the video tag.
                </video>
            </div>

            <!-- Download -->
            <div class="flex items-center gap-3">
                <a href="{{ $videoUrl }}" download
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download Video
                </a>
            </div>
        @elseif($video->status === 'processing')
            <div class="bg-white border border-yellow-200 rounded-2xl p-8 text-center"
                 x-data="{ status: 'processing', elapsed: 0 }"
                 x-init="setInterval(async () => {
                     try {
                         const res = await fetch('/videos/{{ $video->id }}/status', { headers: { 'Accept': 'application/json' } });
                         const data = await res.json();
                         status = data.status;
                         elapsed = data.elapsed_seconds || elapsed;
                         if (data.status === 'completed') window.location.reload();
                     } catch(e) {}
                 }, 5000)">
                <svg class="w-12 h-12 mx-auto text-yellow-500 animate-spin mb-4" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                </svg>
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Generating Your Video</h2>
                <p class="text-sm text-gray-500 mb-2">This usually takes 2-5 minutes</p>
                <p class="text-xs text-gray-400" x-show="elapsed > 0" x-text="'Elapsed: ' + Math.floor(elapsed / 60) + ':' + ('0' + Math.floor(elapsed % 60)).slice(-2)"></p>
            </div>
        @else
            <div class="bg-white border border-red-200 rounded-2xl p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-red-400 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Video Generation Failed</h2>
                <p class="text-sm text-red-500">{{ $video->error_message ?? 'An unknown error occurred.' }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
