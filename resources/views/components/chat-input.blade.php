@props(['sticky' => false])

<div class="w-full {{ $sticky ? 'shrink-0 pt-6 pb-4 px-4 md:px-8' : '' }}"
     @if($sticky) style="background: linear-gradient(to top, #EDE5FF 0%, rgba(245, 240, 255, 0.9) 50%, transparent 100%);" @endif>
    <!-- File Attachment Chips -->
    <template x-if="attachedFiles.length > 0 || (attachedResources && attachedResources.length > 0)">
        <div class="flex flex-wrap gap-2 mb-3 max-w-3xl mx-auto">
            <template x-for="(file, index) in attachedFiles" :key="'f' + index">
                <div class="inline-flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-lg px-3 py-1.5">
                    <svg class="w-4 h-4 text-purple-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span class="text-sm text-purple-700 truncate max-w-[200px]" x-text="file.name"></span>
                    <button @click="removeFile(index)" class="text-purple-400 hover:text-purple-600 transition-colors shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </template>
            <template x-for="(res, index) in (attachedResources || [])" :key="'r' + res.id">
                <div class="inline-flex items-center gap-2 bg-red-50 border border-red-200 rounded-lg px-3 py-1.5">
                    <svg class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span class="text-sm text-red-700 truncate max-w-[200px]" x-text="res.original_filename || res.name"></span>
                    <button @click="removeResource(index)" class="text-red-400 hover:text-red-600 transition-colors shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </template>

    <!-- Chat Input Card -->
    <div class="w-full max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.06)] border border-gray-200 p-4">
            <!-- Input Area -->
            <textarea
                x-model="messageInput"
                @keydown.enter.prevent="if (!$event.shiftKey && messageInput.trim()) sendMessage()"
                placeholder="Ask Me anything"
                rows="1"
                class="w-full resize-none text-sm text-gray-700 placeholder-gray-400 border-none outline-none focus:ring-0 bg-transparent"
                style="min-height: 24px; max-height: 120px; field-sizing: content;"
            ></textarea>

            <!-- Bottom Row -->
            <div class="flex items-center justify-between mt-2 min-w-0">
                <!-- Left Side -->
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Attachment Button with Popup -->
                    <div class="relative" x-data="{ attachOpen: false }">
                        <button @click="attachOpen = !attachOpen"
                                class="w-8 h-8 shrink-0 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-purple-600 hover:border-purple-300 transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>

                        <!-- Attachment Popup Menu -->
                        <div x-show="attachOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             @click.outside="attachOpen = false"
                             class="absolute bottom-full left-0 mb-2 bg-white rounded-xl shadow-lg border border-gray-100 py-2 min-w-[180px] z-50">

                            <!-- Upload PDF -->
                            <button @click="triggerPdfUpload(); attachOpen = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                <span>Upload PDF</span>
                            </button>

                            <!-- Choose from Resources -->
                            <button @click="openResourcesPicker(); attachOpen = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                </svg>
                                <span>Choose from Resources</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mode Dropdown -->
                    <x-mode-dropdown />
                </div>

                <!-- Right Side - Send Button -->
                <button @click="if (messageInput.trim() || (attachedFiles.length > 0 || (attachedResources && attachedResources.length > 0))) sendMessage()"
                        :disabled="!messageInput.trim() && attachedFiles.length === 0 && (!attachedResources || attachedResources.length === 0)"
                        :class="(messageInput.trim() || (attachedFiles.length > 0 || (attachedResources && attachedResources.length > 0))) ? 'bg-[#7C3AED] hover:bg-[#6D28D9]' : 'bg-gray-300 cursor-not-allowed'"
                        class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center transition-colors cursor-pointer">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="19" x2="12" y2="5"/>
                        <polyline points="5 12 12 5 19 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden File Input -->
    <input type="file" x-ref="fileInput" class="hidden" @change="handleFileSelect($event)" />
    <!-- Hidden PDF Input -->
    <input type="file" x-ref="pdfInput" accept="application/pdf" class="hidden" @change="handlePdfUpload($event)" />

    <!-- Resources Picker Modal -->
    <div x-show="resourcesPickerOpen"
         x-transition.opacity
         @click.self="resourcesPickerOpen = false"
         class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Choose a Resource</h3>
                <button @click="resourcesPickerOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-3">
                <template x-if="availableResources.length === 0">
                    <p class="text-sm text-gray-400 italic text-center py-8">No resources yet. Upload a PDF first.</p>
                </template>
                <template x-for="r in availableResources" :key="r.id">
                    <button @click="attachResource(r); resourcesPickerOpen = false"
                            class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-purple-50 rounded-lg transition-colors text-left">
                        <div class="w-8 h-8 shrink-0 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-800 truncate" x-text="r.original_filename || r.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
