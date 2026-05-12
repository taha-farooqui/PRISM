@props(['lesson'])

<div x-data="{ expanded: false }" class="border-t border-gray-50">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Left: lesson title + chevron to toggle description -->
        <button @click="expanded = !expanded" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 text-left">
            <span>{{ $lesson->title }}</span>
            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 shrink-0"
                 :class="expanded ? 'rotate-180' : ''"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>

        <!-- Right: duration + Start button -->
        <div class="flex items-center gap-4 shrink-0">
            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                <span>{{ $lesson->duration_minutes }} mins</span>
            </div>
            <a href="{{ route('lessons.show', [$lesson->course_id, $lesson->id]) }}"
               class="bg-[#7C3AED] text-white text-xs font-semibold px-5 py-2 rounded-lg hover:bg-[#6D28D9] transition-colors">
                Start
            </a>
        </div>
    </div>

    <!-- Expandable description -->
    <div x-show="expanded"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="px-6 pb-4">
        <p class="text-sm text-gray-500 leading-relaxed">{{ $lesson->description }}</p>
    </div>
</div>
