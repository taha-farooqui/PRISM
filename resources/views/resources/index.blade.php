@extends('layouts.app')

@section('title', 'Your Resources - Prism AI')

@section('content')
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-8" x-data="resourcesPage()">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-semibold text-gray-900">Your Resources</h1>
            </div>
            <p class="text-sm text-gray-500 mb-8">Upload PDFs once and use them across your chats.</p>

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-600 text-sm rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Upload zone -->
            <div class="mb-10">
                <label class="block">
                    <div class="border-2 border-dashed border-purple-200 hover:border-purple-400 bg-white rounded-2xl px-6 py-10 text-center cursor-pointer transition-colors"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleDrop($event)"
                         :class="dragOver ? 'border-purple-500 bg-purple-50' : ''">
                        <svg class="w-10 h-10 mx-auto text-purple-400 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <polyline points="9 15 12 12 15 15"/>
                        </svg>
                        <p class="text-sm text-gray-700 font-medium">Drop a PDF here or click to choose</p>
                        <p class="text-xs text-gray-400 mt-1">PDF only, up to 10 MB.</p>
                        <input type="file" accept="application/pdf" class="hidden" @change="handleFile($event.target.files[0])" x-ref="fileInput" />
                        <button type="button" @click="$refs.fileInput.click()"
                                class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-[#7C3AED] text-white text-sm font-medium rounded-xl hover:bg-[#6D28D9] transition-colors">
                            Choose PDF
                        </button>
                        <p class="text-xs text-purple-600 mt-3" x-show="uploading" x-text="uploadStatus"></p>
                        <p class="text-xs text-red-500 mt-3" x-show="uploadError" x-text="uploadError"></p>
                    </div>
                </label>
            </div>

            <!-- Resources Grid -->
            @if($resources->count() === 0)
                <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center shadow-sm">
                    <p class="text-sm text-gray-400 italic">No resources yet. Upload your first PDF above.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($resources as $resource)
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col" id="resource-{{ $resource->id }}">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl bg-red-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $resource->original_filename }}">{{ $resource->original_filename }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $resource->created_at->format('M j, Y') }}
                                        &middot;
                                        {{ number_format($resource->size_bytes / 1024, 1) }} KB
                                    </p>
                                </div>
                            </div>
                            <div class="mt-auto flex items-center gap-2">
                                <a href="{{ route('dashboard') }}?attach_resource={{ $resource->id }}"
                                   class="flex-1 text-center px-3 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 text-sm font-medium rounded-lg transition-colors">
                                    Use in Chat
                                </a>
                                <form method="POST" action="{{ route('resources.destroy', $resource) }}"
                                      onsubmit="return confirm('Delete this resource?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        function resourcesPage() {
            return {
                dragOver: false,
                uploading: false,
                uploadStatus: '',
                uploadError: '',

                handleDrop(event) {
                    this.dragOver = false;
                    const file = event.dataTransfer.files[0];
                    if (file) this.handleFile(file);
                },

                async handleFile(file) {
                    if (!file) return;
                    if (file.type !== 'application/pdf') {
                        this.uploadError = 'Only PDF files are allowed.';
                        return;
                    }

                    this.uploadError = '';
                    this.uploading = true;
                    this.uploadStatus = 'Uploading ' + file.name + '...';

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('/resources', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        if (!response.ok) {
                            const data = await response.json().catch(() => ({}));
                            throw new Error(data.message || 'Upload failed');
                        }

                        this.uploadStatus = 'Upload complete. Refreshing...';
                        window.location.reload();
                    } catch (e) {
                        this.uploading = false;
                        this.uploadError = e.message || 'Upload failed.';
                    }
                }
            };
        }
    </script>
@endsection
