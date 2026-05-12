<header class="flex items-center justify-between px-6 py-4">
    <!-- Left side - Logo -->
    <div class="flex items-center gap-3">
        <!-- Mobile menu button -->
        <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="3" x2="9" y2="21"/>
            </svg>
        </button>

        <!-- Logo -->
        <div class="flex items-center gap-2">
            <img src="{{ asset('assets/images/logo.svg') }}" alt="Prism AI" class="h-8 w-auto">
        </div>
    </div>

    <!-- Right side - Generate New Course Button (AI-styled) -->
    <a href="{{ route('courses.create') }}"
       class="ai-button text-white font-semibold text-sm px-5 py-2.5 rounded-xl flex items-center gap-2.5 no-underline">
        <!-- AI Sparkle Icon -->
        <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L14.09 8.26L20 9.27L15.55 13.97L16.91 20L12 16.9L7.09 20L8.45 13.97L4 9.27L9.91 8.26L12 2Z"
                  fill="white" opacity="0.9"/>
            <path d="M5 3L5.5 5L7 5.5L5.5 6L5 8L4.5 6L3 5.5L4.5 5L5 3Z"
                  fill="white" opacity="0.7"/>
            <path d="M19 16L19.5 18L21 18.5L19.5 19L19 21L18.5 19L17 18.5L18.5 18L19 16Z"
                  fill="white" opacity="0.7"/>
        </svg>
        <span class="hidden sm:inline">Generate New Course</span>
    </a>
</header>
