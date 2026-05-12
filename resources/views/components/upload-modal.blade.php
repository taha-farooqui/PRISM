<div x-show="showUploadModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display: none;">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showUploadModal = false; uploadMode = null; mode = 'ask_any_topic'; $dispatch('mode-reset')"></div>

    <!-- Modal Card -->
    <div x-show="showUploadModal"
         x-transition:enter="transition ease-out duration-200 delay-75"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10"
         x-data="{
             uploadFile: null,
             videoTopic: '',
             isDragging: false,
             isGenerating: false,
             errorMessage: '',

             handleDrop(e) {
                 this.isDragging = false;
                 const files = e.dataTransfer.files;
                 if (files.length > 0) this.uploadFile = files[0];
             },

             handleFileSelect(e) {
                 if (e.target.files.length > 0) this.uploadFile = e.target.files[0];
             },

             canSubmit() {
                 if (uploadMode === 'generate_video') return this.videoTopic.trim().length > 0;
                 return this.uploadFile !== null;
             },

             async submitGenerate() {
                 if (!this.canSubmit() || this.isGenerating) return;
                 this.isGenerating = true;
                 this.errorMessage = '';

                 const csrfToken = document.querySelector('meta[name=csrf-token]').content;

                 try {
                     let response;

                     if (uploadMode === 'generate_video') {
                         // Video mode: send topic as JSON
                         response = await fetch('/videos/generate', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': csrfToken,
                                 'Accept': 'application/json',
                             },
                             body: JSON.stringify({ topic: this.videoTopic.trim() }),
                         });
                     } else {
                         // Quiz/Flashcard mode: send file
                         const formData = new FormData();
                         formData.append('file', this.uploadFile);

                         const url = uploadMode === 'generate_quiz' ? '/quizzes/generate' : '/flashcards/generate';

                         response = await fetch(url, {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN': csrfToken,
                                 'Accept': 'application/json',
                             },
                             body: formData,
                         });
                     }

                     const data = await response.json();

                     if (!response.ok) {
                         this.errorMessage = data.error || 'Something went wrong.';
                         this.isGenerating = false;
                         return;
                     }

                     this.isGenerating = false;

                     // Push artifact message into dashboard chat BEFORE closing modal
                     if (uploadMode === 'generate_quiz') {
                         messages.push({
                             type: 'user',
                             content: 'Generate quiz from: ' + (this.uploadFile ? this.uploadFile.name : 'uploaded file'),
                             files: [],
                         });
                         messages.push({
                             type: 'quiz_artifact',
                             title: data.title,
                             description: data.description,
                             total_questions: data.total_questions,
                             questions_preview: data.questions_preview,
                             quiz_id: data.quiz_id,
                         });
                     } else if (uploadMode === 'generate_flashcards') {
                         messages.push({
                             type: 'user',
                             content: 'Generate flashcards from: ' + (this.uploadFile ? this.uploadFile.name : 'uploaded file'),
                             files: [],
                         });
                         messages.push({
                             type: 'flashcard_artifact',
                             title: data.title,
                             description: data.description,
                             total_cards: data.total_cards,
                             cards: data.cards,
                             set_id: data.set_id,
                         });
                     } else if (uploadMode === 'generate_video') {
                         // Add user message showing what was requested
                         messages.push({
                             type: 'user',
                             content: 'Generate video: ' + data.topic,
                             files: [],
                         });
                         // Add the video artifact
                         messages.push({
                             type: 'video_artifact',
                             video_id: data.video_id,
                             job_id: data.job_id,
                             topic: data.topic,
                             status: 'processing',
                         });
                     }

                     // Dispatch chat-created so sidebar updates with the new conversation
                     if (data.conversation_id && data.conversation_title) {
                         window.dispatchEvent(new CustomEvent('chat-created', {
                             detail: { id: data.conversation_id, title: data.conversation_title }
                         }));
                     }

                     // Close modal AFTER messages are pushed
                     showUploadModal = false;
                     uploadMode = null;
                     mode = 'ask_any_topic';
                     this.uploadFile = null;
                     this.videoTopic = '';
                 } catch (err) {
                     this.errorMessage = 'Network error. Please try again.';
                     this.isGenerating = false;
                 }
             }
         }">

        <!-- Close Button -->
        <button @click="showUploadModal = false; uploadMode = null; mode = 'ask_any_topic'; uploadFile = null; videoTopic = ''; errorMessage = ''; $dispatch('mode-reset')"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <!-- Header -->
        <div class="mb-5">
            <div class="flex items-center gap-3 mb-2">
                <!-- Quiz Icon -->
                <template x-if="uploadMode === 'generate_quiz'">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M7 7h4v4H7zM13 7h4v4h-4zM7 13h4v4H7zM13 13h4v4h-4z"/>
                        </svg>
                    </div>
                </template>
                <!-- Flashcard Icon -->
                <template x-if="uploadMode === 'generate_flashcards'">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="6" width="16" height="14" rx="2"/>
                            <path d="M6 2h12a2 2 0 0 1 2 2v12"/>
                        </svg>
                    </div>
                </template>
                <!-- Video Icon -->
                <template x-if="uploadMode === 'generate_video'">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="15" height="16" rx="2"/>
                            <path d="M17 8l5-3v14l-5-3"/>
                        </svg>
                    </div>
                </template>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900"
                        x-text="uploadMode === 'generate_quiz' ? 'Generate Quiz' : (uploadMode === 'generate_flashcards' ? 'Generate Flashcards' : 'Generate Video')"></h2>
                    <p class="text-sm text-gray-500"
                       x-text="uploadMode === 'generate_quiz' ? 'Upload a lecture file to create quiz questions' : (uploadMode === 'generate_flashcards' ? 'Upload a lecture file to create study flashcards' : 'Enter a math topic to create an animated video')"></p>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <template x-if="errorMessage">
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg" x-text="errorMessage"></div>
        </template>

        <!-- Video Mode: Topic Input -->
        <template x-if="uploadMode === 'generate_video'">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Math Topic</label>
                <input type="text"
                       x-model="videoTopic"
                       @keydown.enter.prevent="submitGenerate()"
                       placeholder="e.g., Pythagorean Theorem, Quadratic Formula"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent" />
                <p class="text-xs text-gray-400 mt-2">Video generation takes 2-5 minutes. You can continue chatting while it processes.</p>
            </div>
        </template>

        <!-- File Upload Mode: Drop Zone (Quiz/Flashcard) -->
        <template x-if="uploadMode !== 'generate_video'">
            <div>
                <div @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)"
                     @click="$refs.modalFileInput.click()"
                     class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200"
                     :class="isDragging ? 'border-purple-400 bg-purple-50' : (uploadFile ? 'border-green-300 bg-green-50' : 'border-gray-200 hover:border-purple-300 hover:bg-purple-50/30')">

                    <template x-if="!uploadFile">
                        <div>
                            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-600 mb-1">Drop your file here or click to browse</p>
                            <p class="text-xs text-gray-400">PDF, DOCX, TXT, PPTX (max 10MB)</p>
                        </div>
                    </template>

                    <template x-if="uploadFile">
                        <div class="flex items-center justify-center gap-3">
                            <svg class="w-8 h-8 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <polyline points="9 15 12 12 15 15"/>
                            </svg>
                            <div class="text-left">
                                <p class="text-sm font-medium text-gray-700" x-text="uploadFile.name"></p>
                                <p class="text-xs text-gray-400" x-text="(uploadFile.size / 1024 / 1024).toFixed(2) + ' MB'"></p>
                            </div>
                            <button @click.stop="uploadFile = null" class="ml-2 text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <input type="file" x-ref="modalFileInput" class="hidden" accept=".pdf,.doc,.docx,.txt,.pptx" @change="handleFileSelect($event)" />
            </div>
        </template>

        <!-- Actions -->
        <div class="flex items-center gap-3 mt-5">
            <button @click="showUploadModal = false; uploadMode = null; mode = 'ask_any_topic'; uploadFile = null; videoTopic = ''; errorMessage = ''; $dispatch('mode-reset')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                Cancel
            </button>
            <button @click="submitGenerate()"
                    :disabled="!canSubmit() || isGenerating"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white rounded-xl transition-all duration-200 flex items-center justify-center gap-2"
                    :class="canSubmit() && !isGenerating ? 'bg-[#7C3AED] hover:bg-[#6D28D9]' : 'bg-gray-300 cursor-not-allowed'">
                <template x-if="isGenerating">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                    </svg>
                </template>
                <span x-text="isGenerating ? 'Generating...' : 'Generate'"></span>
            </button>
        </div>
    </div>
</div>
