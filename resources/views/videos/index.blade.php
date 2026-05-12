@extends('layouts.app')

@section('title', 'Videos - Prism AI')

@section('content')
<div class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Videos</h1>
                <p class="text-sm text-gray-500 mt-1">AI-generated animated educational videos</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-600 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($videos->isEmpty())
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="4" width="15" height="16" rx="2"/>
                    <path d="M17 8l5-3v14l-5-3"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-600 mb-1">No videos yet</h3>
                <p class="text-sm text-gray-400">Select "Generate Video" from the dashboard to create your first video.</p>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-[#7C3AED] text-white text-sm font-medium rounded-xl hover:bg-[#6D28D9] transition-colors">
                    Go to Dashboard
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($videos as $video)
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                {{ $video->status === 'completed' ? 'bg-green-100' : ($video->status === 'processing' ? 'bg-yellow-100' : 'bg-red-100') }}">
                                @if($video->status === 'completed')
                                    <svg class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="4" width="15" height="16" rx="2"/><path d="M17 8l5-3v14l-5-3"/>
                                    </svg>
                                @elseif($video->status === 'processing')
                                    <svg class="w-5 h-5 text-yellow-600 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                @endif
                            </div>
                            @if($video->status !== 'processing')
                                <form method="POST" action="{{ route('videos.destroy', $video) }}" onsubmit="return confirm('Delete this video?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $video->topic }}</h3>
                        <p class="text-sm mb-3">
                            @if($video->status === 'completed')
                                <span class="text-green-600 font-medium">Ready to watch</span>
                            @elseif($video->status === 'processing')
                                <span class="text-yellow-600 font-medium">Generating...</span>
                            @else
                                <span class="text-red-500 font-medium">Failed</span>
                            @endif
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ $video->created_at->diffForHumans() }}</span>
                            @if($video->status === 'completed')
                                <a href="{{ route('videos.show', $video) }}" class="text-sm font-medium text-purple-600 hover:text-purple-700 transition-colors">
                                    Watch &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
