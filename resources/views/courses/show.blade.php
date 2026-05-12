@extends('layouts.app')

@section('title', $course->title . ' - Prism AI')

@section('content')
    <div x-data="{ openWeek: 1 }" class="flex-1 overflow-y-auto chat-scrollbar p-6 md:p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Course header banner -->
            <div class="w-full h-44 rounded-xl bg-gradient-to-r from-[#7C3AED] to-[#A78BFA] flex items-end p-6 mb-6 relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4"></div>
                <div class="absolute bottom-0 left-1/3 w-24 h-24 bg-white/10 rounded-full translate-y-1/2"></div>
                <div class="absolute top-4 right-8 w-12 h-12 bg-white/10 rounded-lg rotate-12"></div>

                <!-- Course title on the banner -->
                <h1 class="text-2xl font-bold text-white relative z-10">{{ $course->title }}</h1>
            </div>

            <!-- Weeks Accordion -->
            <div class="space-y-3">
                @foreach($course->weeks as $week)
                    <div class="border border-gray-100 rounded-xl overflow-hidden bg-white">
                        <!-- Week Header -->
                        <button @click="openWeek = openWeek === {{ $week->order }} ? null : {{ $week->order }}"
                                class="w-full flex items-center justify-between px-6 py-4 bg-white hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-6 md:gap-8 flex-wrap">
                                <!-- Week Title -->
                                <span class="text-base font-bold text-gray-900">{{ $week->title }}</span>

                                <!-- Classes Left -->
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                                    </svg>
                                    <span class="text-xs text-gray-500">{{ $week->classesLeftCount() }} Classes Left</span>
                                </div>

                                <!-- Progress Ring -->
                                <div class="flex items-center gap-2">
                                    <svg class="w-7 h-7 -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="2.5"/>
                                        <circle cx="18" cy="18" r="14" fill="none" stroke="#7C3AED" stroke-width="2.5"
                                            stroke-dasharray="{{ $week->progressPercentage() * 0.88 }} 88" stroke-linecap="round"/>
                                    </svg>
                                    <span class="text-xs text-gray-500">{{ $week->progressPercentage() }}%</span>
                                </div>

                                <!-- Quizzes -->
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/>
                                    </svg>
                                    <span class="text-xs text-gray-500">{{ $week->quizzesCount() }} Quizzes</span>
                                </div>
                            </div>

                            <!-- Chevron -->
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 shrink-0"
                                 :class="openWeek === {{ $week->order }} ? 'rotate-180' : ''"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>

                        <!-- Lessons (Expanded) -->
                        <div x-show="openWeek === {{ $week->order }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="bg-white">
                            @foreach($week->lessons as $lesson)
                                <x-lesson-row :lesson="$lesson" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
