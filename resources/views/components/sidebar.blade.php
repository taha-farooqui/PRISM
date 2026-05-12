@props(['recentChats' => []])

<aside class="fixed top-0 left-0 h-screen bg-white border-r border-gray-100 z-40 flex flex-col transition-all duration-300 ease-in-out"
       :class="{
           'w-64': sidebarExpanded,
           'w-16': !sidebarExpanded,
           '-translate-x-full lg:translate-x-0': !mobileMenuOpen,
           'translate-x-0': mobileMenuOpen
       }">

    <!-- Toggle Button -->
    <div class="p-4">
        <button @click="sidebarExpanded = !sidebarExpanded; mobileMenuOpen = false"
                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                title="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="3" x2="9" y2="21"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-2 space-y-5 overflow-y-auto pb-4">

        {{-- ──────────────  GROUP 1: LEARN  ────────────── --}}
        <div>
            <h3 x-show="sidebarExpanded" class="px-3 text-[14px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Learn</h3>
            <div class="space-y-0.5">
                <x-sidebar-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" label="Home">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('progress') }}" :active="request()->routeIs('progress')" label="Your Progress">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('my-courses') }}" :active="request()->routeIs('my-courses') || request()->routeIs('courses.*') || request()->routeIs('lessons.*')" label="My Courses">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>
            </div>
        </div>

        {{-- ──────────────  GROUP 2: LIBRARY  ────────────── --}}
        <div>
            <h3 x-show="sidebarExpanded" class="px-3 text-[14px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Library</h3>
            <div class="space-y-0.5">
                <x-sidebar-item href="{{ route('quizzes.index') }}" :active="request()->routeIs('quizzes.*')" label="Quizzes">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('flashcards.index') }}" :active="request()->routeIs('flashcards.*')" label="Flashcards">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="6" width="16" height="14" rx="2"/>
                            <path d="M6 2h12a2 2 0 0 1 2 2v12"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('videos.index') }}" :active="request()->routeIs('videos.*')" label="Videos">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="15" height="16" rx="2"/>
                            <path d="M17 8l5-3v14l-5-3"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>

                <x-sidebar-item href="{{ route('resources.index') }}" :active="request()->routeIs('resources.*')" label="Resources">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                    </x-slot:icon>
                </x-sidebar-item>
            </div>
        </div>

        {{-- ──────────────  GROUP 3: HISTORY (only if has chats)  ────────────── --}}
        <div x-show="sidebarExpanded"
             x-data="{ chats: {{ json_encode($recentChats) }} }"
             @chat-created.window="chats.unshift({ id: $event.detail.id, title: $event.detail.title }); if (chats.length > 20) chats.pop()"
             x-cloak>
            <template x-if="chats.length > 0">
                <div>
                    <div class="flex items-center justify-between px-3 mb-2">
                        <h3 class="text-[14px] font-semibold text-gray-400 uppercase tracking-wider">History</h3>
                        <span class="text-[11px] font-semibold text-gray-300" x-text="chats.length"></span>
                    </div>
                    <div class="space-y-0.5">
                        <template x-for="chat in chats" :key="chat.id">
                            <a :href="'/dashboard?chat=' + chat.id"
                               @click="if (window.location.pathname === '/dashboard') { $event.preventDefault(); $dispatch('load-conversation', { id: chat.id }); }"
                               class="block px-3 py-2.5 text-[15px] font-medium text-gray-700 hover:text-purple-600 hover:bg-purple-50 rounded-lg cursor-pointer transition-colors truncate"
                               :title="chat.title"
                               x-text="chat.title">
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </nav>

    {{-- ──────────────  USER PROFILE  ────────────── --}}
    <div x-data="{ userMenuOpen: false }" class="mt-auto border-t border-gray-100 relative">
        {{-- Trigger --}}
        <button @click="userMenuOpen = !userMenuOpen"
                class="w-full flex items-center gap-3 px-3 py-3 hover:bg-gray-50 active:bg-gray-100 transition-colors cursor-pointer group"
                :class="userMenuOpen ? 'bg-gray-50' : ''">
            {{-- Avatar with online indicator --}}
            <div class="relative shrink-0">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center ring-2 ring-white group-hover:ring-purple-100 transition-all">
                    <span class="text-white text-sm font-semibold">
                        {{ strtoupper(substr(Auth::user()->name ?? Auth::user()->email, 0, 1)) }}
                    </span>
                </div>
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full" title="Online"></span>
            </div>

            {{-- Name + email + chevron (only when expanded) --}}
            <div x-show="sidebarExpanded" class="flex-1 flex items-center justify-between min-w-0">
                <div class="text-left min-w-0">
                    <p class="text-[14px] font-semibold text-gray-800 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[12px] text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
                <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200 ml-2"
                     :class="userMenuOpen ? 'rotate-180' : ''"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15"/>
                </svg>
            </div>
        </button>

        {{-- Popup Menu --}}
        <div x-show="userMenuOpen"
             @click.away="userMenuOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             class="absolute z-50 bg-white rounded-xl shadow-xl border border-gray-200 py-2"
             :class="sidebarExpanded
                 ? 'bottom-full left-2 right-2 mb-2'
                 : 'bottom-0 left-full ml-2 w-56'"
             x-cloak>

            {{-- User Profile Section --}}
            <div class="px-4 py-3 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">
                            {{ strtoupper(substr(Auth::user()->name ?? Auth::user()->email, 0, 1)) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Menu Items --}}
            <div class="py-1">
                <a href="#"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Settings</span>
                </a>

                <a href="#"
                   class="flex items-center justify-between px-4 py-2.5 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                        <span>Language</span>
                    </div>
                    <span class="text-xs text-gray-400">EN</span>
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <span>Get help</span>
                </a>
            </div>

            <div class="border-t border-gray-100 my-1"></div>

            <div class="py-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <span>Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

<style>
    [x-cloak] { display: none !important; }
</style>
