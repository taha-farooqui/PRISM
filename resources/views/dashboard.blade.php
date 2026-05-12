@extends('layouts.app')

@section('title', 'Dashboard - Prism AI')

@section('content')
    <div x-data="chatApp()" x-init="init()"
         @load-conversation.window="loadConversation($event.detail.id)"
         @new-topic.window="newTopic()"
         @mode-changed.window="
             mode = $event.detail.mode;
             if (mode === 'generate_quiz' || mode === 'generate_flashcards' || mode === 'generate_video') {
                 uploadMode = mode;
                 showUploadModal = true;
             }
         "
         class="flex-1 flex flex-col h-full">
        <!-- Success Message -->
        @if(session('success'))
            <div class="p-4">
                <div class="p-4 bg-green-50 border border-green-200 text-green-600 text-sm rounded-lg max-w-2xl mx-auto">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <!-- Welcome State (shown when no messages and not loading a conversation) -->
        <template x-if="messages.length === 0 && !isLoading">
            <div class="flex-1 flex flex-col items-center justify-center w-full px-4 md:px-8 py-12">
                <!-- Welcome Section -->
                <div class="text-center mb-8">
                    <h1 class="text-5xl md:text-6xl text-gray-900" x-text="welcomeHeadline"></h1>
                </div>

                <!-- Chat Input (Centered) -->
                <x-chat-input />

                <!-- Suggestion Chips -->
                <div class="flex flex-wrap justify-center gap-2 mt-5 max-w-2xl mx-auto">
                    <button @click="messageInput = 'Explain Operating Systems in simple terms'"
                        class="group flex items-center gap-2 px-4 py-2.5 bg-white/70 backdrop-blur-sm border border-gray-200/60 rounded-full text-sm text-gray-600 hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50/50 hover:shadow-sm transition-all duration-200 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        Explain Operating Systems
                    </button>

                    <button @click="messageInput = 'What is Binary Search Tree?'"
                        class="group flex items-center gap-2 px-4 py-2.5 bg-white/70 backdrop-blur-sm border border-gray-200/60 rounded-full text-sm text-gray-600 hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50/50 hover:shadow-sm transition-all duration-200 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        What is Binary Search Tree?
                    </button>

                    <button @click="messageInput = 'Create a study plan for Database Systems'"
                        class="group flex items-center gap-2 px-4 py-2.5 bg-white/70 backdrop-blur-sm border border-gray-200/60 rounded-full text-sm text-gray-600 hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50/50 hover:shadow-sm transition-all duration-200 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Create a study plan
                    </button>

                    <button @click="messageInput = 'Generate flashcards for Computer Networks'"
                        class="group flex items-center gap-2 px-4 py-2.5 bg-white/70 backdrop-blur-sm border border-gray-200/60 rounded-full text-sm text-gray-600 hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50/50 hover:shadow-sm transition-all duration-200 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="16" height="14" rx="2"/><path d="M6 2h12a2 2 0 0 1 2 2v12"/></svg>
                        Generate flashcards
                    </button>

                    <button @click="messageInput = 'Quiz me on Software Engineering concepts'"
                        class="group flex items-center gap-2 px-4 py-2.5 bg-white/70 backdrop-blur-sm border border-gray-200/60 rounded-full text-sm text-gray-600 hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50/50 hover:shadow-sm transition-all duration-200 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Quiz me on Software Engineering
                    </button>
                </div>
            </div>
        </template>

        <!-- Chat State (shown when messages exist or loading a conversation) -->
        <template x-if="messages.length > 0 || isLoading">
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Chat Messages Area (Scrollable) - TRANSPARENT background -->
                <div class="flex-1 overflow-y-auto chat-scrollbar px-4 py-6 bg-transparent"
                     x-ref="chatContainer"
                     @scroll="checkScroll()">
                    <div class="max-w-3xl mx-auto space-y-6">
                        <template x-for="(message, index) in messages" :key="index">
                            <div>
                                <!-- User Message (Right aligned) -->
                                <template x-if="message.type === 'user'">
                                    <div class="flex justify-end">
                                        <div class="max-w-[80%]">
                                            <!-- Attached Files -->
                                            <template x-if="message.files && message.files.length > 0">
                                                <div class="flex flex-wrap gap-2 mb-2 justify-end">
                                                    <template x-for="file in message.files" :key="file.name">
                                                        <div class="inline-flex items-center gap-2 bg-purple-100 rounded-lg px-3 py-1.5">
                                                            <svg class="w-4 h-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                                <polyline points="14 2 14 8 20 8"/>
                                                            </svg>
                                                            <span class="text-sm text-purple-700" x-text="file.name"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <!-- Message Bubble -->
                                            <div class="bg-[#7C3AED] text-white rounded-2xl rounded-br-md px-4 py-3">
                                                <p class="text-base whitespace-pre-wrap" x-text="message.content"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- AI Message (Left aligned) - NO side logo, actions below -->
                                <template x-if="message.type === 'ai'">
                                    <div class="flex justify-start">
                                        <div class="max-w-[80%]">
                                            <!-- Response text — rendered markdown -->
                                            <div class="md-content text-base text-gray-700 leading-relaxed max-w-none"
                                                 x-html="renderMarkdown(message.content)"
                                                 x-init="$nextTick(() => renderMath($el))"></div>

                                            <!-- Action icons row -->
                                            <div class="flex items-center gap-1 mt-3">
                                                <!-- Copy button -->
                                                <button @click="copyToClipboard(message.content)"
                                                        class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                                        title="Copy">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                    </svg>
                                                </button>

                                                <!-- Like button -->
                                                <button @click="message.liked = !message.liked; message.disliked = false"
                                                        class="p-1.5 rounded-md transition-colors"
                                                        :class="message.liked ? 'text-purple-600 bg-purple-50' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'"
                                                        title="Like">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                                                    </svg>
                                                </button>

                                                <!-- Dislike button -->
                                                <button @click="message.disliked = !message.disliked; message.liked = false"
                                                        class="p-1.5 rounded-md transition-colors"
                                                        :class="message.disliked ? 'text-red-500 bg-red-50' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'"
                                                        title="Dislike">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/>
                                                    </svg>
                                                </button>

                                                <!-- Regenerate button -->
                                                <button @click="regenerateResponse(index)"
                                                        class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                                        title="Regenerate">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="23 4 23 10 17 10"/>
                                                        <polyline points="1 20 1 14 7 14"/>
                                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- PRISM logo below actions -->
                                            <div class="mt-2">
                                                <img src="{{ asset('assets/images/logo.svg') }}" alt="PRISM" class="w-10 h-10">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <!-- Quiz Artifact -->
                                <template x-if="message.type === 'quiz_artifact'">
                                    <div class="flex justify-start">
                                        <div class="max-w-[85%] w-full">
                                            <div class="bg-white border border-purple-200 rounded-2xl p-5 shadow-sm">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-semibold text-gray-900" x-text="message.title"></h3>
                                                        <p class="text-sm text-gray-500" x-text="message.total_questions + ' multiple-choice questions'"></p>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-3" x-text="message.description"></p>
                                                <div class="space-y-2 mb-4">
                                                    <template x-for="(q, qi) in (message.questions_preview || [])" :key="qi">
                                                        <div class="flex items-start gap-2 text-sm text-gray-500">
                                                            <span class="text-purple-500 font-medium shrink-0" x-text="(qi + 1) + '.'"></span>
                                                            <span x-text="q"></span>
                                                        </div>
                                                    </template>
                                                    <p class="text-xs text-gray-400" x-show="message.total_questions > 3">...and more</p>
                                                </div>
                                                <a :href="'/quizzes/' + message.quiz_id"
                                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#7C3AED] text-white text-sm font-medium rounded-xl hover:bg-[#6D28D9] transition-colors">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                    Take Quiz
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Flashcard Artifact -->
                                <template x-if="message.type === 'flashcard_artifact'">
                                    <div class="flex justify-start">
                                        <div class="max-w-[85%] w-full">
                                            <div class="bg-white border border-purple-200 rounded-2xl p-5 shadow-sm">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="16" height="14" rx="2"/><path d="M6 2h12a2 2 0 0 1 2 2v12"/></svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-semibold text-gray-900" x-text="message.title"></h3>
                                                        <p class="text-sm text-gray-500" x-text="message.total_cards + ' flashcards'"></p>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-4" x-text="message.description"></p>

                                                <!-- Inline Flashcard Viewer -->
                                                <div x-data="{ fcIndex: 0, fcFlipped: false }" class="mb-4">
                                                    <div class="flashcard-container mx-auto" style="height: 200px;">
                                                        <div class="flashcard-inner relative w-full h-full cursor-pointer"
                                                             :class="fcFlipped ? 'flipped' : ''"
                                                             @click="fcFlipped = !fcFlipped">
                                                            <!-- Front -->
                                                            <div class="flashcard-front absolute inset-0 bg-gradient-to-br from-purple-50 to-white border border-purple-200 rounded-xl p-5 flex items-center justify-center">
                                                                <p class="text-center text-gray-800 font-medium" x-text="message.cards[fcIndex].front"></p>
                                                            </div>
                                                            <!-- Back -->
                                                            <div class="flashcard-back absolute inset-0 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl p-5 flex items-center justify-center">
                                                                <p class="text-center text-white" x-text="message.cards[fcIndex].back"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Navigation -->
                                                    <div class="flex items-center justify-between mt-3">
                                                        <button @click="if(fcIndex > 0) { fcIndex--; fcFlipped = false }"
                                                                :class="fcIndex > 0 ? 'text-purple-600 hover:bg-purple-50' : 'text-gray-300 cursor-not-allowed'"
                                                                class="p-2 rounded-lg transition-colors">
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                                        </button>
                                                        <span class="text-sm text-gray-500" x-text="(fcIndex + 1) + ' / ' + message.cards.length"></span>
                                                        <button @click="if(fcIndex < message.cards.length - 1) { fcIndex++; fcFlipped = false }"
                                                                :class="fcIndex < message.cards.length - 1 ? 'text-purple-600 hover:bg-purple-50' : 'text-gray-300 cursor-not-allowed'"
                                                                class="p-2 rounded-lg transition-colors">
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                                        </button>
                                                    </div>
                                                    <p class="text-xs text-gray-400 text-center mt-1">Click card to flip</p>
                                                </div>

                                                <a :href="'/flashcards/' + message.set_id"
                                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#7C3AED] text-white text-sm font-medium rounded-xl hover:bg-[#6D28D9] transition-colors">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="16" height="14" rx="2"/><path d="M6 2h12a2 2 0 0 1 2 2v12"/></svg>
                                                    View All Flashcards
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Video Artifact -->
                                <template x-if="message.type === 'video_artifact'">
                                    <div class="flex justify-start">
                                        <div class="max-w-[85%] w-full"
                                             x-data="{
                                                 videoStatus: message.status || 'processing',
                                                 videoUrl: message.video_url || null,
                                                 subtitleUrl: message.subtitle_url || null,
                                                 subtitlesOn: false,
                                                 elapsedSeconds: 0,
                                                 errorMsg: message.error || '',
                                                 progressPhase: 'Initializing',
                                                 progressPercent: 0,
                                                 pollInterval: null,
                                                 timeInterval: null,
                                                 startTime: Date.now(),

                                                 init() {
                                                     if (this.videoStatus === 'processing') {
                                                         this.startPolling();
                                                         this.timeInterval = setInterval(() => { this.elapsedSeconds = Math.floor((Date.now() - this.startTime) / 1000); }, 1000);
                                                     }
                                                 },

                                                 startPolling() {
                                                     this.pollInterval = setInterval(() => this.checkStatus(), 2000);
                                                 },

                                                 async checkStatus() {
                                                     try {
                                                         const res = await fetch('/videos/' + message.video_id + '/status', {
                                                             headers: { 'Accept': 'application/json' },
                                                         });
                                                         const data = await res.json();
                                                         this.videoStatus = data.status;
                                                         if (data.progress_phase) this.progressPhase = data.progress_phase;
                                                         if (typeof data.progress_percent === 'number') this.progressPercent = data.progress_percent;

                                                         if (data.status === 'completed') {
                                                             this.videoUrl = data.video_url;
                                                             this.subtitleUrl = data.subtitle_url || null;
                                                             this.progressPercent = 100;
                                                             clearInterval(this.pollInterval);
                                                             clearInterval(this.timeInterval);
                                                         } else if (data.status === 'failed') {
                                                             this.errorMsg = data.error || 'Generation failed';
                                                             clearInterval(this.pollInterval);
                                                             clearInterval(this.timeInterval);
                                                         }
                                                     } catch (e) {}
                                                 },

                                                 toggleSubtitles($refs) {
                                                     this.subtitlesOn = !this.subtitlesOn;
                                                     const video = $refs.chatVideoPlayer;
                                                     if (!video) return;
                                                     for (let i = 0; i < video.textTracks.length; i++) {
                                                         video.textTracks[i].mode = this.subtitlesOn ? 'showing' : 'hidden';
                                                     }
                                                 },

                                                 formatTime(seconds) {
                                                     const m = Math.floor(seconds / 60);
                                                     const s = Math.floor(seconds % 60);
                                                     return m + ':' + (s < 10 ? '0' : '') + s;
                                                 }
                                             }"
                                             x-init="init()">
                                            <div class="bg-white border border-purple-200 rounded-2xl p-5 shadow-sm">
                                                <!-- Processing State -->
                                                <template x-if="videoStatus === 'processing'">
                                                    <div>
                                                        <div class="flex items-center gap-3 mb-4">
                                                            <div class="relative">
                                                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center shadow-lg shadow-purple-500/40">
                                                                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                                                    </svg>
                                                                </div>
                                                                <div class="absolute -inset-1 rounded-xl bg-gradient-to-br from-purple-400 to-pink-500 blur opacity-40 animate-pulse"></div>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <h3 class="font-semibold text-gray-900">Generating Video</h3>
                                                                <p class="text-sm text-gray-500 truncate" x-text="message.topic"></p>
                                                            </div>
                                                            <span class="text-xs text-gray-400 font-medium" x-text="formatTime(elapsedSeconds)"></span>
                                                        </div>

                                                        {{-- Animated phase card --}}
                                                        <div class="rounded-xl p-4 relative overflow-hidden"
                                                             style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4C1D95 100%);">
                                                            <div class="absolute top-0 left-1/4 w-32 h-32 bg-purple-500 rounded-full blur-3xl opacity-30 animate-pulse"></div>
                                                            <div class="absolute bottom-0 right-1/4 w-32 h-32 bg-pink-500 rounded-full blur-3xl opacity-25 animate-pulse" style="animation-delay: 1s"></div>

                                                            <div class="relative z-10">
                                                                <div class="flex items-center gap-2 mb-3">
                                                                    <div class="w-1.5 h-1.5 bg-purple-300 rounded-full animate-pulse"></div>
                                                                    <span class="text-sm font-medium text-white"
                                                                          :key="progressPhase"
                                                                          x-transition:enter="transition ease-out duration-300"
                                                                          x-transition:enter-start="opacity-0 translate-y-1"
                                                                          x-transition:enter-end="opacity-100 translate-y-0"
                                                                          x-text="progressPhase || 'Initializing'"></span>
                                                                </div>

                                                                {{-- Progress bar --}}
                                                                <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden mb-2">
                                                                    <div class="h-full bg-gradient-to-r from-purple-400 via-pink-400 to-purple-400 rounded-full transition-all duration-700 ease-out"
                                                                         :style="`width: ${progressPercent}%; background-size: 200% 100%; animation: shimmer 2s linear infinite;`"></div>
                                                                </div>

                                                                {{-- Step pips --}}
                                                                <div class="grid grid-cols-5 gap-1">
                                                                    <div class="h-1 rounded-full transition-all" :class="progressPercent >= 5 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                                                    <div class="h-1 rounded-full transition-all" :class="progressPercent >= 20 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                                                    <div class="h-1 rounded-full transition-all" :class="progressPercent >= 35 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                                                    <div class="h-1 rounded-full transition-all" :class="progressPercent >= 55 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                                                    <div class="h-1 rounded-full transition-all" :class="progressPercent >= 90 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                                                </div>

                                                                <div class="flex items-center justify-between mt-2 text-[11px]">
                                                                    <span class="text-purple-200/70" x-text="progressPercent + '%'"></span>
                                                                    <span class="text-purple-200/50">Crafting your video...</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Completed State -->
                                                <template x-if="videoStatus === 'completed'">
                                                    <div>
                                                        <div class="flex items-center gap-3 mb-3">
                                                            <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="font-semibold text-gray-900">Video Ready!</h3>
                                                                <p class="text-sm text-gray-500" x-text="message.topic"></p>
                                                            </div>
                                                        </div>
                                                        <!-- Inline Video Player -->
                                                        <div class="rounded-xl overflow-hidden bg-black mb-3" x-show="videoUrl">
                                                            <div class="relative">
                                                                <video
                                                                    x-ref="chatVideoPlayer"
                                                                    :src="videoUrl"
                                                                    controls
                                                                    crossorigin="anonymous"
                                                                    class="w-full"
                                                                    style="max-height: 300px;">
                                                                    <template x-if="subtitleUrl">
                                                                        <track
                                                                            kind="subtitles"
                                                                            :src="subtitleUrl"
                                                                            srclang="en"
                                                                            label="English"
                                                                            default>
                                                                    </template>
                                                                </video>
                                                                <template x-if="subtitleUrl">
                                                                    <button
                                                                        @click="toggleSubtitles($refs)"
                                                                        class="absolute top-2 right-2 z-10 inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold backdrop-blur-sm transition-all opacity-90 hover:opacity-100"
                                                                        :class="subtitlesOn ? 'bg-white text-gray-900' : 'bg-black/60 text-white border border-white/30'"
                                                                        :title="subtitlesOn ? 'Hide subtitles' : 'Show subtitles'">
                                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                            <rect x="2" y="6" width="20" height="14" rx="2" ry="2"/>
                                                                            <path d="M7 12h3M7 16h6M14 12h3M16 16h1"/>
                                                                        </svg>
                                                                        <span x-text="subtitlesOn ? 'CC On' : 'CC'"></span>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <a :href="'/videos/' + message.video_id"
                                                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#7C3AED] text-white text-sm font-medium rounded-xl hover:bg-[#6D28D9] transition-colors">
                                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="15" height="16" rx="2"/><path d="M17 8l5-3v14l-5-3"/></svg>
                                                            Watch Full Screen
                                                        </a>
                                                    </div>
                                                </template>

                                                <!-- Failed State -->
                                                <template x-if="videoStatus === 'failed'">
                                                    <div>
                                                        <div class="flex items-center gap-3 mb-3">
                                                            <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                                            </div>
                                                            <div class="flex-1">
                                                                <h3 class="font-semibold text-gray-900">Video Generation Failed</h3>
                                                                <p class="text-sm text-red-500" x-text="errorMsg"></p>
                                                            </div>
                                                        </div>
                                                        <div class="flex justify-end">
                                                            <button @click="dismissArtifact(index)"
                                                                    class="text-xs px-3 py-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                                                Dismiss
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Skeleton Loading Animation - NO side logo -->
                        <template x-if="isLoading">
                            <div class="flex justify-start">
                                <div class="max-w-[80%]">
                                    <div class="space-y-3">
                                        <div class="h-3 bg-purple-200/60 rounded-full w-72 animate-pulse"></div>
                                        <div class="h-3 bg-purple-200/60 rounded-full w-96 animate-pulse" style="animation-delay: 0.15s"></div>
                                        <div class="h-3 bg-purple-200/60 rounded-full w-80 animate-pulse" style="animation-delay: 0.3s"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Scroll to bottom button -->
                <div class="relative">
                    <div x-show="showScrollButton"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="absolute -top-14 left-1/2 -translate-x-1/2 z-10">
                        <button @click="scrollToBottom()"
                                class="w-9 h-9 rounded-full bg-white border border-gray-200 shadow-md flex items-center justify-center text-gray-500 hover:text-purple-600 hover:border-purple-300 hover:shadow-lg transition-all duration-200 cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Sticky Chat Input (Bottom) with gradient fade -->
                <x-chat-input :sticky="true" />
            </div>
        </template>

        <!-- Upload Modal for Quiz/Flashcard Generation -->
        <x-upload-modal />
    </div>

    <script>
        function chatApp() {
            const userName = '{{ Auth::user()->name ? explode(' ', Auth::user()->name)[0] : 'there' }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const headlines = [
                `Welcome, ${userName}`,
                `Let's Study, ${userName}`,
                `What shall we think through?`,
                `Ready to learn, ${userName}?`,
                `What's on your mind today?`,
                `Let's explore together, ${userName}`,
                `Time to level up, ${userName}`,
                `What would you like to master?`,
                `Curiosity awaits, ${userName}`,
                `Let's dive in, ${userName}`,
                `What are we learning today?`,
                `Knowledge is power, ${userName}`,
                `Ask me anything, ${userName}`,
                `Your learning journey continues`,
                `Let's make progress, ${userName}`
            ];

            return {
                messages: [],
                messageInput: '',
                attachedFiles: [],
                attachedResources: [],
                availableResources: [],
                resourcesPickerOpen: false,
                isLoading: false,
                fileType: null,
                showScrollButton: false,
                welcomeHeadline: headlines[Math.floor(Math.random() * headlines.length)],
                conversationId: null,
                mode: 'ask_any_topic',
                showUploadModal: false,
                uploadMode: null,

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const chatId = params.get('chat');
                    if (chatId) {
                        this.loadConversation(chatId);
                    }
                    const attachResourceId = params.get('attach_resource');
                    if (attachResourceId) {
                        this.fetchAndAttachResource(attachResourceId);
                    }
                },

                async sendMessage() {
                    if (!this.messageInput.trim() && this.attachedFiles.length === 0) return;

                    const userMessage = this.messageInput.trim();
                    const isNewConversation = !this.conversationId;

                    const displayFiles = [
                        ...this.attachedFiles,
                        ...this.attachedResources.map(r => ({ name: r.original_filename || r.name }))
                    ];

                    this.messages.push({
                        type: 'user',
                        content: userMessage,
                        files: displayFiles
                    });

                    const resourceIds = this.attachedResources.map(r => r.id);

                    this.messageInput = '';
                    this.attachedFiles = [];
                    this.attachedResources = [];
                    this.isLoading = true;

                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const response = await fetch('/chat/send', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                message: userMessage,
                                conversation_id: this.conversationId,
                                mode: this.mode,
                                resource_ids: resourceIds,
                            }),
                        });

                        if (!response.ok) throw new Error('Failed to get response');

                        const data = await response.json();

                        this.conversationId = data.conversation_id;
                        window.history.replaceState({}, '', '/dashboard?chat=' + data.conversation_id);

                        this.isLoading = false;
                        this.messages.push({
                            type: 'ai',
                            content: data.message.content,
                            liked: false,
                            disliked: false,
                        });

                        if (isNewConversation) {
                            window.dispatchEvent(new CustomEvent('chat-created', {
                                detail: { id: data.conversation_id, title: data.title }
                            }));
                        }

                        this.$nextTick(() => this.scrollToBottom());
                    } catch (error) {
                        this.isLoading = false;
                        this.messages.push({
                            type: 'ai',
                            content: 'Sorry, something went wrong. Please try again.',
                            liked: false,
                            disliked: false,
                        });
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                async loadConversation(id) {
                    this.messages = [];
                    this.conversationId = null;
                    this.isLoading = true;

                    try {
                        const response = await fetch('/chat/' + id, {
                            headers: { 'Accept': 'application/json' },
                        });

                        if (!response.ok) throw new Error('Failed to load conversation');

                        const data = await response.json();

                        this.conversationId = data.conversation.id;
                        this.mode = data.conversation.mode || 'ask_any_topic';
                        this.messages = data.messages.map(m => ({
                            type: m.role === 'assistant' ? 'ai' : 'user',
                            content: m.content,
                            liked: false,
                            disliked: false,
                        }));

                        // Append artifact card for generation-mode conversations
                        if (data.video) {
                            this.messages.push({
                                type: 'video_artifact',
                                video_id: data.video.video_id,
                                topic: data.video.topic,
                                status: data.video.status,
                                video_url: data.video.video_url,
                                subtitle_url: data.video.subtitle_url,
                                error: data.video.error,
                            });
                        }
                        if (data.quiz) {
                            this.messages.push({
                                type: 'quiz_artifact',
                                quiz_id: data.quiz.quiz_id,
                                title: data.quiz.title,
                                description: data.quiz.description,
                                total_questions: data.quiz.total_questions,
                                questions_preview: data.quiz.questions_preview || [],
                            });
                        }
                        if (data.flashcard_set) {
                            this.messages.push({
                                type: 'flashcard_artifact',
                                set_id: data.flashcard_set.set_id,
                                title: data.flashcard_set.title,
                                description: data.flashcard_set.description,
                                total_cards: data.flashcard_set.total_cards,
                                cards: Array.isArray(data.flashcard_set.cards) ? data.flashcard_set.cards : [],
                            });
                        }

                        window.history.replaceState({}, '', '/dashboard?chat=' + id);
                        this.isLoading = false;
                        this.$nextTick(() => this.scrollToBottom());
                    } catch (error) {
                        this.isLoading = false;
                    }
                },

                newTopic() {
                    this.messages = [];
                    this.conversationId = null;
                    this.messageInput = '';
                    this.attachedFiles = [];
                    this.isLoading = false;

                    const greetings = [
                        `Welcome, ${userName}`, `Let's Study, ${userName}`, `What shall we think through?`,
                        `Ready to learn, ${userName}?`, `What's on your mind today?`,
                        `Ask me anything, ${userName}`, `Your learning journey continues`
                    ];
                    this.welcomeHeadline = greetings[Math.floor(Math.random() * greetings.length)];
                    window.history.replaceState({}, '', '/dashboard');
                },

                scrollToBottom() {
                    if (this.$refs.chatContainer) {
                        this.$refs.chatContainer.scrollTo({
                            top: this.$refs.chatContainer.scrollHeight,
                            behavior: 'smooth'
                        });
                        this.showScrollButton = false;
                    }
                },

                checkScroll() {
                    const el = this.$refs.chatContainer;
                    if (!el) return;
                    const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
                    this.showScrollButton = distanceFromBottom > 100;
                },

                renderMarkdown(text) {
                    if (!text) return '';
                    if (typeof marked === 'undefined') {
                        return text.replace(/\n/g, '<br>');
                    }
                    marked.setOptions({
                        gfm: true,
                        breaks: true,
                        headerIds: false,
                        mangle: false,
                    });
                    // Render with default renderer first, then transform <pre><code> blocks afterwards.
                    // This avoids marked.js v15 breaking-change incompatibilities with custom renderers.
                    let raw = marked.parse(text);

                    // Sanitize first
                    if (typeof DOMPurify !== 'undefined') {
                        raw = DOMPurify.sanitize(raw, {
                            ADD_TAGS: ['math', 'mrow', 'mi', 'mn', 'mo', 'msup', 'msub', 'mfrac'],
                        });
                    }

                    // Now transform <pre><code class="language-X">...</code></pre> blocks into our styled wrapper
                    const tmp = document.createElement('div');
                    tmp.innerHTML = raw;
                    tmp.querySelectorAll('pre > code').forEach(codeEl => {
                        const pre = codeEl.parentElement;
                        const classMatch = (codeEl.className || '').match(/language-(\S+)/);
                        const lang = classMatch ? classMatch[1] : 'plaintext';
                        const rawCode = codeEl.textContent || '';

                        // Syntax highlight with hljs if available
                        let highlightedHtml = '';
                        if (typeof hljs !== 'undefined') {
                            try {
                                const result = hljs.getLanguage(lang)
                                    ? hljs.highlight(rawCode, { language: lang, ignoreIllegals: true })
                                    : hljs.highlightAuto(rawCode);
                                highlightedHtml = result.value;
                            } catch (e) {
                                highlightedHtml = codeEl.innerHTML;
                            }
                        } else {
                            highlightedHtml = codeEl.innerHTML;
                        }

                        // Encode raw code for copy button
                        const encoded = btoa(unescape(encodeURIComponent(rawCode)));
                        const displayLang = lang === 'plaintext' ? 'text' : lang;

                        const wrapper = document.createElement('div');
                        wrapper.className = 'code-block';
                        wrapper.innerHTML = `
                            <div class="code-block-header">
                                <span class="code-block-lang">${displayLang}</span>
                                <button type="button" class="code-block-copy" data-code="${encoded}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <span class="copy-label">Copy</span>
                                </button>
                            </div>
                            <pre><code class="hljs language-${lang}"></code></pre>
                        `;
                        wrapper.querySelector('code').innerHTML = highlightedHtml;
                        pre.replaceWith(wrapper);
                    });

                    return tmp.innerHTML;
                },

                renderMath(element) {
                    if (!element) return;
                    // Wire up copy buttons inside code blocks
                    element.querySelectorAll('.code-block-copy').forEach(btn => {
                        if (btn.dataset.bound) return;
                        btn.dataset.bound = '1';
                        btn.addEventListener('click', () => {
                            try {
                                const raw = decodeURIComponent(escape(atob(btn.dataset.code || '')));
                                navigator.clipboard.writeText(raw).then(() => {
                                    const label = btn.querySelector('.copy-label');
                                    if (label) {
                                        const orig = label.textContent;
                                        label.textContent = 'Copied!';
                                        btn.classList.add('copied');
                                        setTimeout(() => {
                                            label.textContent = orig;
                                            btn.classList.remove('copied');
                                        }, 1500);
                                    }
                                });
                            } catch (e) {}
                        });
                    });
                    if (typeof renderMathInElement !== 'undefined') {
                        try {
                            renderMathInElement(element, {
                                delimiters: [
                                    { left: '$$', right: '$$', display: true },
                                    { left: '\\[', right: '\\]', display: true },
                                    { left: '$', right: '$', display: false },
                                    { left: '\\(', right: '\\)', display: false },
                                ],
                                throwOnError: false,
                                errorColor: '#999',
                                ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                            });
                        } catch (e) {}
                    }
                },

                copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {});
                },

                async regenerateResponse(index) {
                    if (!this.conversationId) return;

                    this.messages.splice(index, 1);
                    this.isLoading = true;
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const response = await fetch('/chat/' + this.conversationId + '/regenerate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) throw new Error('Failed to regenerate');

                        const data = await response.json();

                        this.isLoading = false;
                        this.messages.push({
                            type: 'ai',
                            content: data.message.content,
                            liked: false,
                            disliked: false,
                        });
                        this.$nextTick(() => this.scrollToBottom());
                    } catch (error) {
                        this.isLoading = false;
                        this.messages.push({
                            type: 'ai',
                            content: 'Sorry, failed to regenerate. Please try again.',
                            liked: false,
                            disliked: false,
                        });
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                triggerFileUpload(type) {
                    this.fileType = type;
                    const accept = type === 'image' ? 'image/*' : 'video/*';
                    this.$refs.fileInput.accept = accept;
                    this.$refs.fileInput.click();
                },

                handleFileSelect(event) {
                    const files = event.target.files;
                    if (files.length > 0) {
                        for (let i = 0; i < files.length; i++) {
                            this.attachedFiles.push({
                                name: files[i].name,
                                type: files[i].type,
                                size: files[i].size
                            });
                        }
                    }
                    event.target.value = '';
                },

                removeFile(index) {
                    this.attachedFiles.splice(index, 1);
                },

                connectDrive() {
                    alert('Google Drive connection coming soon!');
                },

                triggerPdfUpload() {
                    if (this.$refs.pdfInput) this.$refs.pdfInput.click();
                },

                async handlePdfUpload(event) {
                    const file = event.target.files[0];
                    event.target.value = '';
                    if (!file) return;
                    if (file.type !== 'application/pdf') {
                        alert('Only PDF files are allowed.');
                        return;
                    }
                    const formData = new FormData();
                    formData.append('file', file);
                    try {
                        const res = await fetch('/resources', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                        if (!res.ok) throw new Error('Upload failed');
                        const data = await res.json();
                        this.attachedResources.push(data);
                    } catch (e) {
                        alert('Failed to upload PDF.');
                    }
                },

                async openResourcesPicker() {
                    try {
                        const res = await fetch('/resources/list.json', { headers: { 'Accept': 'application/json' } });
                        if (res.ok) {
                            this.availableResources = await res.json();
                        }
                    } catch (e) {}
                    this.resourcesPickerOpen = true;
                },

                attachResource(resource) {
                    if (!this.attachedResources.find(r => r.id === resource.id)) {
                        this.attachedResources.push(resource);
                    }
                },

                removeResource(index) {
                    this.attachedResources.splice(index, 1);
                },

                dismissArtifact(index) {
                    if (typeof index !== 'number') return;
                    this.messages.splice(index, 1);
                },

                async fetchAndAttachResource(id) {
                    try {
                        const res = await fetch('/resources/' + id, { headers: { 'Accept': 'application/json' } });
                        if (res.ok) {
                            const data = await res.json();
                            this.attachResource(data);
                            // strip param from URL
                            const u = new URL(window.location.href);
                            u.searchParams.delete('attach_resource');
                            window.history.replaceState({}, '', u.toString());
                        }
                    } catch (e) {}
                }
            };
        }
    </script>
@endsection
