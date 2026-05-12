@extends('layouts.app')

@section('title', $lesson->title . ' - Prism AI')

@section('content')
    <div class="flex-1 overflow-y-auto chat-scrollbar p-6 md:p-8"
         x-data="{
             videoStatus: '{{ $video->status }}',
             videoUrl: {!! $videoUrl ? "'" . e($videoUrl) . "'" : 'null' !!},
             videoId: {{ $video->id }},
             errorMsg: '{{ addslashes($video->error_message ?? '') }}',
             progressPhase: '{{ addslashes($video->progress_phase ?? 'Initializing') }}',
             progressPercent: {{ (int)($video->progress_percent ?? 0) }},
             elapsedSeconds: 0,
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
                     const res = await fetch('/videos/' + this.videoId + '/status', {
                         headers: { 'Accept': 'application/json' },
                     });
                     const data = await res.json();
                     this.videoStatus = data.status;
                     if (data.progress_phase) this.progressPhase = data.progress_phase;
                     if (typeof data.progress_percent === 'number') this.progressPercent = data.progress_percent;

                     if (data.status === 'completed') {
                         this.videoUrl = data.video_url;
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

             formatTime(seconds) {
                 const m = Math.floor(seconds / 60);
                 const s = Math.floor(seconds % 60);
                 return m + ':' + (s < 10 ? '0' : '') + s;
             }
         }"
         x-init="init()">
        <div class="max-w-6xl mx-auto">
            <!-- Back Arrow -->
            <a href="{{ route('courses.show', $course->id) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-5">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Back to course</span>
            </a>

            <!-- Video + Side Panel -->
            <div class="flex gap-4 mb-6">
                <!-- Video Area (Left ~60%) -->
                <div class="w-3/5">

                    {{-- ── COMPLETED: Show real video player ── --}}
                    <template x-if="videoStatus === 'completed' && videoUrl">
                        <div class="aspect-video bg-black rounded-xl overflow-hidden shadow-lg">
                            <video :src="videoUrl" controls class="w-full h-full" autoplay></video>
                        </div>
                    </template>

                    {{-- ── PROCESSING: Animated progress experience ── --}}
                    <template x-if="videoStatus === 'processing'">
                        <div class="aspect-video rounded-xl flex flex-col items-center justify-center relative overflow-hidden"
                             style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4C1D95 100%);">

                            {{-- Animated background blobs --}}
                            <div class="absolute inset-0 overflow-hidden">
                                <div class="absolute top-1/4 left-1/4 w-48 h-48 bg-purple-500 rounded-full blur-3xl opacity-30 animate-pulse"></div>
                                <div class="absolute bottom-1/4 right-1/4 w-56 h-56 bg-blue-500 rounded-full blur-3xl opacity-25 animate-pulse" style="animation-delay: 1s"></div>
                                <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-pink-500 rounded-full blur-3xl opacity-20 animate-pulse" style="animation-delay: 2s"></div>
                            </div>

                            {{-- Animated grid pattern --}}
                            <div class="absolute inset-0 opacity-10"
                                 style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 30px 30px; animation: gridShift 4s linear infinite;"></div>

                            <div class="relative z-10 flex flex-col items-center gap-4 px-6 w-full max-w-md">
                                {{-- Animated icon --}}
                                <div class="relative">
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center shadow-2xl shadow-purple-500/50 animate-float">
                                        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                        </svg>
                                    </div>
                                    <div class="absolute -inset-2 rounded-2xl bg-gradient-to-br from-purple-400 to-pink-500 blur-xl opacity-50 animate-pulse"></div>
                                </div>

                                {{-- Phase title (animated text swap) --}}
                                <div class="text-center min-h-[3rem]">
                                    <h3 class="text-white font-semibold text-base"
                                        :key="progressPhase"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-2"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-text="progressPhase || 'Initializing'"></h3>
                                </div>

                                {{-- Progress bar --}}
                                <div class="w-full">
                                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden backdrop-blur-sm">
                                        <div class="h-full bg-gradient-to-r from-purple-400 via-pink-400 to-purple-400 rounded-full transition-all duration-700 ease-out"
                                             :style="`width: ${progressPercent}%; background-size: 200% 100%; animation: shimmer 2s linear infinite;`"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2 text-xs">
                                        <span class="text-purple-200/70" x-text="`${progressPercent}%`"></span>
                                        <span class="text-purple-200/70" x-text="formatTime(elapsedSeconds)"></span>
                                    </div>
                                </div>

                                {{-- Phase steps indicator --}}
                                <div class="grid grid-cols-5 gap-1.5 w-full mt-1">
                                    <div class="h-1 rounded-full transition-all duration-500" :class="progressPercent >= 5 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                    <div class="h-1 rounded-full transition-all duration-500" :class="progressPercent >= 20 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                    <div class="h-1 rounded-full transition-all duration-500" :class="progressPercent >= 35 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                    <div class="h-1 rounded-full transition-all duration-500" :class="progressPercent >= 55 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                    <div class="h-1 rounded-full transition-all duration-500" :class="progressPercent >= 90 ? 'bg-purple-400' : 'bg-white/10'"></div>
                                </div>

                                <p class="text-purple-200/60 text-xs mt-1">PRISM AI is crafting your video</p>
                            </div>
                        </div>
                    </template>

                    {{-- ── FAILED: Error state ── --}}
                    <template x-if="videoStatus === 'failed'">
                        <div class="aspect-video bg-gray-900 rounded-xl flex flex-col items-center justify-center">
                            <div class="flex flex-col items-center gap-3 px-6">
                                <div class="w-14 h-14 rounded-full bg-red-500/20 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                </div>
                                <h3 class="text-white font-semibold">Generation Failed</h3>
                                <p class="text-red-400 text-sm text-center max-w-sm" x-text="errorMsg"></p>
                                <a href="" @click.prevent="window.location.reload()" class="mt-2 px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors">
                                    Retry
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Side Panel: Lesson Overview + Phase Steps -->
                <div class="w-2/5 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-3">Lesson Overview</h3>
                    <div class="space-y-2.5 text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span>{{ $lesson->duration_minutes }} minutes</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="2" y="4" width="15" height="16" rx="2"/><path d="M17 8l5-3v14l-5-3"/>
                            </svg>
                            <span x-text="videoStatus === 'completed' ? 'Video ready' : (videoStatus === 'processing' ? 'Generating...' : 'Generation failed')"></span>
                        </div>
                    </div>

                    {{-- Live phase checklist (only during processing) --}}
                    <template x-if="videoStatus === 'processing'">
                        <div class="mt-5 pt-4 border-t border-gray-100">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Generation Steps</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                                          :class="progressPercent >= 15 ? 'bg-green-100 text-green-600' : (progressPercent >= 5 ? 'bg-purple-100 text-purple-600 animate-pulse' : 'bg-gray-100 text-gray-400')">
                                        <template x-if="progressPercent >= 15"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></template>
                                        <template x-if="progressPercent < 15">1</template>
                                    </span>
                                    <span :class="progressPercent >= 5 ? 'text-gray-700 font-medium' : 'text-gray-400'">Researching topic</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                                          :class="progressPercent >= 30 ? 'bg-green-100 text-green-600' : (progressPercent >= 15 ? 'bg-purple-100 text-purple-600 animate-pulse' : 'bg-gray-100 text-gray-400')">
                                        <template x-if="progressPercent >= 30"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></template>
                                        <template x-if="progressPercent < 30">2</template>
                                    </span>
                                    <span :class="progressPercent >= 15 ? 'text-gray-700 font-medium' : 'text-gray-400'">Recording narration</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                                          :class="progressPercent >= 50 ? 'bg-green-100 text-green-600' : (progressPercent >= 35 ? 'bg-purple-100 text-purple-600 animate-pulse' : 'bg-gray-100 text-gray-400')">
                                        <template x-if="progressPercent >= 50"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></template>
                                        <template x-if="progressPercent < 50">3</template>
                                    </span>
                                    <span :class="progressPercent >= 35 ? 'text-gray-700 font-medium' : 'text-gray-400'">Designing visuals</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                                          :class="progressPercent >= 90 ? 'bg-green-100 text-green-600' : (progressPercent >= 55 ? 'bg-purple-100 text-purple-600 animate-pulse' : 'bg-gray-100 text-gray-400')">
                                        <template x-if="progressPercent >= 90"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></template>
                                        <template x-if="progressPercent < 90">4</template>
                                    </span>
                                    <span :class="progressPercent >= 55 ? 'text-gray-700 font-medium' : 'text-gray-400'">Rendering animations</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                                          :class="progressPercent >= 100 ? 'bg-green-100 text-green-600' : (progressPercent >= 92 ? 'bg-purple-100 text-purple-600 animate-pulse' : 'bg-gray-100 text-gray-400')">
                                        <template x-if="progressPercent >= 100"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></template>
                                        <template x-if="progressPercent < 100">5</template>
                                    </span>
                                    <span :class="progressPercent >= 92 ? 'text-gray-700 font-medium' : 'text-gray-400'">Mixing audio + video</span>
                                </li>
                            </ul>
                        </div>
                    </template>

                    @if($lesson->description)
                        <div class="mt-5 pt-4 border-t border-gray-100">
                            <p class="text-sm text-gray-500 leading-relaxed">{{ $lesson->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Lesson Title & Description -->
            <h1 class="text-xl font-bold text-gray-900 mt-2 mb-3">{{ $lesson->title }}</h1>
            @if($lesson->description)
                <p class="text-sm text-gray-500 leading-relaxed max-w-3xl">{{ $lesson->description }}</p>
            @endif
        </div>
    </div>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes gridShift {
            from { background-position: 0 0; }
            to { background-position: 30px 30px; }
        }
        @keyframes shimmer {
            from { background-position: 200% 0; }
            to { background-position: -200% 0; }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
    </style>
@endsection
