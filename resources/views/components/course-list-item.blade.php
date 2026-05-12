@props(['course'])

<div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 mb-3">
    <div class="flex items-center justify-between gap-4">
        <!-- Left - Title -->
        <h3 class="text-sm font-semibold text-gray-900 truncate min-w-0 flex-1">
            {{ $course->title }}
        </h3>

        <!-- Middle - Classes Left & Continue Button -->
        <div class="flex items-center gap-6">
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

        <!-- Right - Progress & Delete -->
        <div class="flex items-center gap-4">
            <!-- Progress Ring -->
            <div class="flex items-center gap-2">
                <svg class="w-8 h-8 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="3"/>
                    <circle cx="18" cy="18" r="14" fill="none" stroke="#7C3AED" stroke-width="3"
                        stroke-dasharray="{{ $course->progress * 0.88 }} 88"
                        stroke-linecap="round"/>
                </svg>
                <span class="text-xs font-semibold text-gray-700">{{ $course->progress }}%</span>
            </div>

            <!-- Delete Button -->
            <form method="POST" action="{{ route('courses.destroy', $course->id) }}"
                  onsubmit="return confirm('Are you sure you want to delete this course?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-400 hover:text-red-600 p-1 transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
