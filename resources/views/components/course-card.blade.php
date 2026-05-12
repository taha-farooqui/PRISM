@props(['course'])

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
    <!-- Thumbnail Area -->
    <div class="w-full h-36 bg-gray-50">
        @if($course->thumbnail)
            <img src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-36 object-cover">
        @endif
    </div>

    <!-- Content Area -->
    <div class="p-4">
        <!-- Title + Progress Row -->
        <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2">
                {{ $course->title }}
            </h3>
            <!-- Progress Ring -->
            <div class="flex items-center gap-1.5 shrink-0">
                <svg class="w-7 h-7 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="3"/>
                    <circle cx="18" cy="18" r="14" fill="none" stroke="#7C3AED" stroke-width="3"
                        stroke-dasharray="{{ $course->progress * 0.88 }} 88"
                        stroke-linecap="round"/>
                </svg>
                <span class="text-xs font-semibold text-gray-700">{{ $course->progress }}%</span>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="flex items-center justify-between">
            <!-- Classes Left -->
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <span class="text-xs text-gray-500">{{ $course->classes_left }} Classes Left</span>
            </div>

            <!-- Continue Button -->
            <a href="{{ route('courses.show', $course->id) }}" class="bg-[#7C3AED] text-white text-xs font-semibold px-4 py-1.5 rounded-md hover:bg-[#6D28D9] transition-colors">
                Continue
            </a>
        </div>
    </div>
</div>
