@extends('layouts.app')

@section('title', 'Quiz Results - Prism AI')

@section('content')
<div class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('quizzes.index') }}" class="text-sm text-gray-400 hover:text-purple-600 transition-colors mb-4 inline-flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Quizzes
        </a>

        <!-- Score Card -->
        <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6 text-center">
            @php $percentage = round(($attempt->score / $attempt->total_questions) * 100); @endphp
            <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center {{ $percentage >= 70 ? 'bg-green-100' : ($percentage >= 40 ? 'bg-yellow-100' : 'bg-red-100') }}">
                <span class="text-xl font-bold {{ $percentage >= 70 ? 'text-green-600' : ($percentage >= 40 ? 'text-yellow-600' : 'text-red-600') }}">{{ $percentage }}%</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900">{{ $quiz->title }}</h1>
            <p class="text-gray-500 mt-1">Score: {{ $attempt->score }} / {{ $attempt->total_questions }}</p>
            <p class="text-xs text-gray-400 mt-1">Completed {{ $attempt->completed_at->diffForHumans() }}</p>
        </div>

        <!-- Questions Review -->
        <div class="space-y-4">
            @foreach($questions as $question)
                @php
                    $userAnswer = $attempt->answers[$question->id] ?? null;
                    $isCorrect = $userAnswer && strtoupper($userAnswer) === strtoupper($question->correct_answer);
                @endphp
                <div class="bg-white border {{ $isCorrect ? 'border-green-200' : 'border-red-200' }} rounded-2xl p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 {{ $isCorrect ? 'bg-green-100' : 'bg-red-100' }}">
                            @if($isCorrect)
                                <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            @endif
                        </div>
                        <p class="text-gray-900 font-medium">{{ $question->question }}</p>
                    </div>

                    @if($userAnswer)
                        <p class="text-sm text-gray-500 ml-10">Your answer: <span class="font-medium {{ $isCorrect ? 'text-green-600' : 'text-red-500' }}">{{ $userAnswer }} - {{ $question->{'option_' . strtolower($userAnswer)} }}</span></p>
                    @endif

                    @if(!$isCorrect)
                        <p class="text-sm text-gray-500 ml-10">Correct answer: <span class="font-medium text-green-600">{{ $question->correct_answer }} - {{ $question->{'option_' . strtolower($question->correct_answer)} }}</span></p>
                    @endif

                    @if($question->explanation)
                        <p class="text-sm text-blue-700 bg-blue-50 rounded-lg p-3 mt-3 ml-10">{{ $question->explanation }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-center gap-3 mt-8">
            <a href="{{ route('quizzes.show', $quiz) }}" class="px-5 py-2.5 text-sm font-medium text-purple-600 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors">
                Retake Quiz
            </a>
            <a href="{{ route('quizzes.index') }}" class="px-5 py-2.5 text-sm font-medium text-white bg-[#7C3AED] rounded-xl hover:bg-[#6D28D9] transition-colors">
                Back to Quizzes
            </a>
        </div>
    </div>
</div>
@endsection
