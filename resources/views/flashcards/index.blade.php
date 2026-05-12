@extends('layouts.app')

@section('title', 'Flashcards - Prism AI')

@section('content')
<div class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Flashcards</h1>
                <p class="text-sm text-gray-500 mt-1">Review and study your AI-generated flashcards</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-600 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($flashcardSets->isEmpty())
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="6" width="16" height="14" rx="2"/>
                    <path d="M6 2h12a2 2 0 0 1 2 2v12"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-600 mb-1">No flashcard sets yet</h3>
                <p class="text-sm text-gray-400">Select "Generate Flashcards" from the dashboard to create your first set.</p>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-[#7C3AED] text-white text-sm font-medium rounded-xl hover:bg-[#6D28D9] transition-colors">
                    Go to Dashboard
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($flashcardSets as $set)
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="6" width="16" height="14" rx="2"/>
                                    <path d="M6 2h12a2 2 0 0 1 2 2v12"/>
                                </svg>
                            </div>
                            <form method="POST" action="{{ route('flashcards.destroy', $set) }}" onsubmit="return confirm('Delete this flashcard set?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $set->title }}</h3>
                        <p class="text-sm text-gray-500 mb-3">{{ $set->total_cards }} cards</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ $set->created_at->diffForHumans() }}</span>
                            <a href="{{ route('flashcards.show', $set) }}" class="text-sm font-medium text-purple-600 hover:text-purple-700 transition-colors">
                                Study &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
