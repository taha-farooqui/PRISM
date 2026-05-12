@extends('layouts.app')

@section('title', 'Your Progress - Prism AI')

@section('content')
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-semibold text-gray-900 mb-2">Your Progress</h1>
            <p class="text-sm text-gray-500 mb-8">A snapshot of your learning activity on Prism.</p>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Courses</p>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $stats['courses'] }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Quizzes</p>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $stats['quizzes'] }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Videos</p>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $stats['videos'] }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Flashcard Sets</p>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $stats['flashcard_sets'] }}</p>
                </div>
            </div>

            <!-- Average Quiz Score -->
            <div class="bg-gradient-to-br from-purple-600 to-purple-800 text-white rounded-2xl p-6 mb-10 shadow-sm">
                <p class="text-sm uppercase tracking-wider opacity-80">Average Quiz Score</p>
                <p class="text-5xl font-bold mt-2">{{ $averageScore }}%</p>
                <p class="text-sm mt-2 opacity-80">Across all your quiz attempts.</p>
            </div>

            <!-- Recent Activities -->
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Activity</h2>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                @if($activities->count() === 0)
                    <p class="px-5 py-8 text-sm text-gray-400 italic text-center">No activity yet. Start learning!</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($activities as $activity)
                            <li class="px-5 py-4 flex items-start gap-3">
                                <div class="w-8 h-8 shrink-0 rounded-full bg-purple-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800">{{ $activity->description }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
