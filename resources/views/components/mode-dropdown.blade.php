<div x-data="{
        open: false,
        mode: 'ask_any_topic',
        modes: {
            ask_any_topic: { label: 'Ask Any topic', desc: 'Instant answers to all your questions.' },
            generate_video: { label: 'Generate Video', desc: 'Create animated math explanation videos.' },
            generate_quiz: { label: 'Generate Quiz', desc: 'Create quizzes from your lecture files.' },
            generate_flashcards: { label: 'Generate Flashcards', desc: 'Turn your notes into flashcards.' },
            ask_ai_tutor: { label: 'Ask Ai Tutor', desc: 'Animated blackboard explanations.' }
        }
     }"
     @mode-reset.window="mode = 'ask_any_topic'; open = false"
     class="relative">

    <!-- Trigger Button -->
    <button @click="open = !open"
            class="text-purple-600 text-sm font-medium flex items-center gap-1 cursor-pointer hover:text-purple-700 transition-colors">
        <span x-text="modes[mode].label"></span>
        <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="open = false"
         class="absolute bottom-full left-0 mb-2 bg-white rounded-xl shadow-lg border border-gray-100 py-2 w-72 z-50">

        <!-- Ask Any Topic (with description) -->
        <button @click="mode = 'ask_any_topic'; open = false; $dispatch('mode-changed', { mode: 'ask_any_topic' })"
                class="w-full px-4 py-2.5 hover:bg-purple-50 cursor-pointer rounded-lg mx-auto text-left flex items-start gap-3"
                style="width: calc(100% - 8px); margin-left: 4px;">
            <!-- Icon -->
            <span class="text-gray-600 mt-0.5">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </span>
            <!-- Content -->
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-900">Ask Any topic</span>
                    <svg x-show="mode === 'ask_any_topic'" class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">Instant answers to all your questions.</p>
            </div>
        </button>

        <!-- Divider -->
        <div class="border-t border-gray-100 my-1"></div>

        <!-- Generate Video -->
        <button @click="mode = 'generate_video'; open = false; $dispatch('mode-changed', { mode: 'generate_video' })"
                class="w-full px-4 py-2 hover:bg-purple-50 cursor-pointer rounded-lg text-left flex items-center gap-3"
                style="width: calc(100% - 8px); margin-left: 4px;">
            <span class="text-gray-500">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="4" width="15" height="16" rx="2"/>
                    <path d="M17 8l5-3v14l-5-3"/>
                </svg>
            </span>
            <span class="text-sm font-medium text-gray-700">Generate Video</span>
            <svg x-show="mode === 'generate_video'" class="w-4 h-4 text-purple-600 ml-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </button>

        <!-- Generate Quiz -->
        <button @click="mode = 'generate_quiz'; open = false; $dispatch('mode-changed', { mode: 'generate_quiz' })"
                class="w-full px-4 py-2 hover:bg-purple-50 cursor-pointer rounded-lg text-left flex items-center gap-3"
                style="width: calc(100% - 8px); margin-left: 4px;">
            <span class="text-gray-500">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M7 7h4v4H7zM13 7h4v4h-4zM7 13h4v4H7zM13 13h4v4h-4z"/>
                </svg>
            </span>
            <span class="text-sm font-medium text-gray-700">Generate Quiz</span>
            <svg x-show="mode === 'generate_quiz'" class="w-4 h-4 text-purple-600 ml-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </button>

        <!-- Generate Flashcards -->
        <button @click="mode = 'generate_flashcards'; open = false; $dispatch('mode-changed', { mode: 'generate_flashcards' })"
                class="w-full px-4 py-2 hover:bg-purple-50 cursor-pointer rounded-lg text-left flex items-center gap-3"
                style="width: calc(100% - 8px); margin-left: 4px;">
            <span class="text-gray-500">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="6" width="16" height="14" rx="2"/>
                    <path d="M6 2h12a2 2 0 0 1 2 2v12"/>
                </svg>
            </span>
            <span class="text-sm font-medium text-gray-700">Generate Flashcards</span>
            <svg x-show="mode === 'generate_flashcards'" class="w-4 h-4 text-purple-600 ml-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </button>

        <!-- Divider -->
        <div class="border-t border-gray-100 my-1"></div>

        <!-- Ask AI Tutor (with description) -->
        <button @click="mode = 'ask_ai_tutor'; open = false; $dispatch('mode-changed', { mode: 'ask_ai_tutor' })"
                class="w-full px-4 py-2.5 hover:bg-purple-50 cursor-pointer rounded-lg text-left flex items-start gap-3"
                style="width: calc(100% - 8px); margin-left: 4px;">
            <!-- Icon -->
            <span class="text-gray-600 mt-0.5">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
            </span>
            <!-- Content -->
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-900">Ask Ai Tutor</span>
                    <svg x-show="mode === 'ask_ai_tutor'" class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">Animated blackboard explanations.</p>
            </div>
        </button>
    </div>
</div>
