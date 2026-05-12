<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Course - Prism AI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body class="bg-[#F0F0F5] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-6xl h-[90vh] max-h-[750px] bg-white rounded-2xl shadow-xl overflow-hidden flex">
        <!-- Left Half - Image (Hidden on mobile) -->
        <div class="hidden md:block w-1/2 p-2">
            <div class="w-full h-full rounded-l-xl overflow-hidden">
                <img
                    src="{{ asset('assets/images/left-image.jpg') }}"
                    alt="Personalization"
                    class="w-full h-full object-cover grayscale"
                >
            </div>
        </div>

        <!-- Right Half - Form -->
        <div class="w-full md:w-1/2 bg-[#F5F3FF] flex flex-col p-8 md:p-12 rounded-2xl md:rounded-l-none md:rounded-r-xl relative overflow-y-auto"
             x-data="{
                step: 1,
                courseName: '{{ old('course_name', '') }}',
                searchQuery: '{{ old('course_name', '') }}',
                fileName: null,
                loading: false,
                dragging: false,
                dropdownOpen: false,
                suggestions: [
                    'Operating Systems',
                    'Data Structures and Algorithms',
                    'Software Engineering',
                    'Database Management Systems',
                    'Computer Networks',
                    'Artificial Intelligence',
                    'Machine Learning',
                    'Web Development',
                    'Cyber Security',
                    'Business Management'
                ],
                get filteredSuggestions() {
                    if (!this.searchQuery) return this.suggestions;
                    return this.suggestions.filter(s =>
                        s.toLowerCase().includes(this.searchQuery.toLowerCase())
                    );
                },
                selectCourse(name) {
                    this.courseName = name;
                    this.searchQuery = name;
                    this.dropdownOpen = false;
                },
                goToStep2() {
                    if (!this.courseName.trim() && !this.searchQuery.trim()) return;
                    if (!this.courseName.trim()) this.courseName = this.searchQuery.trim();
                    this.step = 2;
                }
             }">

            <!-- Step 1: Course Name Selection -->
            <div x-show="step === 1"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 -translate-x-4"
                 class="flex-1 flex flex-col">

                <!-- Back Arrow -->
                <a href="{{ route('my-courses') }}" class="inline-block mb-6 self-start">
                    <svg class="w-6 h-6 text-gray-500 hover:text-gray-700 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
                    </svg>
                </a>

                <div class="flex-1 flex flex-col justify-center max-w-[400px] mx-auto w-full">
                    <!-- Logo -->
                    <img src="{{ asset('assets/images/logo.svg') }}" alt="PRISM" class="w-14 h-14 mb-6">

                    <!-- Heading -->
                    <h1 class="text-3xl font-bold text-gray-900 leading-tight mb-8">
                        Your personalized<br>learning starts in<br>seconds.
                    </h1>

                    <!-- Error Message -->
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Question: Course Name -->
                    <div class="mb-6">
                        <label class="block text-base font-semibold text-gray-900 mb-3">
                            What do you want to study?
                        </label>

                        <!-- Custom Dropdown -->
                        <div class="relative" @click.away="dropdownOpen = false">
                            <!-- Input field -->
                            <div class="relative">
                                <input type="text"
                                    x-model="searchQuery"
                                    @focus="dropdownOpen = true"
                                    @input="dropdownOpen = true; courseName = searchQuery"
                                    @keydown.enter.prevent="goToStep2()"
                                    placeholder="e.g. Operating Systems, Data Structures..."
                                    class="w-full bg-white border border-gray-200 rounded-lg py-3.5 px-4 pr-10 text-sm text-gray-700 placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
                                <!-- Chevron toggle -->
                                <button type="button" @click="dropdownOpen = !dropdownOpen" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Dropdown list -->
                            <div x-show="dropdownOpen"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">

                                <template x-for="(item, index) in filteredSuggestions" :key="index">
                                    <button type="button"
                                        @click="selectCourse(item)"
                                        class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors border-b border-gray-50 last:border-b-0"
                                        :class="courseName === item ? 'bg-purple-50 text-purple-600 font-medium' : ''">
                                        <span x-text="item"></span>
                                    </button>
                                </template>

                                <!-- Show "Use custom name" option when user types something not in list -->
                                <div x-show="searchQuery.trim() && filteredSuggestions.length === 0"
                                     class="px-4 py-3 text-sm text-gray-500">
                                    Press Continue to use "<span class="font-medium text-gray-700" x-text="searchQuery"></span>"
                                </div>
                            </div>
                        </div>

                        @error('course_name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Buttons Row -->
                    <div class="flex items-center gap-3 mb-4">
                        <button type="button" @click="goToStep2()"
                            :disabled="!searchQuery.trim() && !courseName.trim()"
                            class="bg-[#7C3AED] text-white font-semibold text-sm px-8 py-3 rounded-lg hover:bg-[#6D28D9] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Continue
                        </button>
                        <button type="button" @click="goToStep2()"
                            :disabled="!searchQuery.trim() && !courseName.trim()"
                            class="border-2 border-[#7C3AED] text-[#7C3AED] font-semibold text-sm px-8 py-3 rounded-lg hover:bg-purple-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Skip this step
                        </button>
                    </div>

                    <!-- Skip Personalization -->
                    <a href="{{ route('dashboard') }}"
                       class="block w-full text-center border-2 border-[#7C3AED] text-[#7C3AED] font-semibold text-sm py-3 rounded-lg hover:bg-purple-50 transition-colors">
                        Skip Personalization
                    </a>
                </div>
            </div>

            <!-- Step 2: Upload Resources -->
            <div x-show="step === 2"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 -translate-x-4"
                 class="flex-1 flex flex-col">

                <!-- Back Arrow (goes back to Step 1) -->
                <button type="button" @click="step = 1" class="inline-block mb-6 self-start">
                    <svg class="w-6 h-6 text-gray-500 hover:text-gray-700 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
                    </svg>
                </button>

                <div class="flex-1 flex flex-col justify-center max-w-[400px] mx-auto w-full">
                    <!-- Logo -->
                    <img src="{{ asset('assets/images/logo.svg') }}" alt="PRISM" class="w-14 h-14 mb-6">

                    <!-- Heading -->
                    <h1 class="text-3xl font-bold text-gray-900 leading-tight mb-8">
                        Upload your resources<br>to customize your<br>course.
                    </h1>

                    <!-- Form -->
                    <form method="POST" action="{{ route('courses.generate') }}" enctype="multipart/form-data"
                          @submit="loading = true">
                        @csrf

                        <!-- Hidden course name input -->
                        <input type="hidden" name="course_name" :value="courseName">

                        <!-- Upload Resources -->
                        <div class="mb-6">
                            <label class="block text-base font-semibold text-gray-900 mb-2">
                                Upload Resources (optional)
                            </label>
                            <p class="text-xs text-gray-400 mb-3">Upload PDFs, documents, or syllabi to customize your course</p>

                            <div class="relative">
                                <label
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="dragging = false; fileName = $event.dataTransfer.files[0]?.name; $refs.fileInput.files = $event.dataTransfer.files"
                                    :class="dragging ? 'border-purple-500 bg-purple-50' : 'border-gray-200 bg-white'"
                                    class="flex flex-col items-center justify-center w-full py-8 border-2 border-dashed rounded-lg cursor-pointer hover:border-purple-400 transition-colors">
                                    <svg class="w-10 h-10 text-gray-300 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <span x-show="!fileName" class="text-sm text-gray-400">Drop files here or click to upload</span>
                                    <span x-show="fileName" class="text-sm text-purple-600 font-medium" x-text="fileName"></span>
                                    <input type="file" name="resource" x-ref="fileInput" @change="fileName = $event.target.files[0]?.name"
                                        accept=".pdf,.doc,.docx,.txt,.pptx" class="hidden">
                                </label>
                            </div>
                            @error('resource')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Buttons Row -->
                        <div class="flex items-center gap-3">
                            <button type="submit" :disabled="loading"
                                class="bg-[#7C3AED] text-white font-semibold text-sm px-8 py-3 rounded-lg hover:bg-[#6D28D9] transition-colors disabled:opacity-70 disabled:cursor-not-allowed">
                                <span x-show="!loading">Generate</span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75"/>
                                    </svg>
                                    Generating...
                                </span>
                            </button>
                            <button type="submit" name="skip_upload" value="1" :disabled="loading"
                                class="border-2 border-[#7C3AED] text-[#7C3AED] font-semibold text-sm px-8 py-3 rounded-lg hover:bg-purple-50 transition-colors disabled:opacity-70 disabled:cursor-not-allowed">
                                Skip this step
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
