<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard - Prism AI')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Markdown rendering: marked.js (tables, lists, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- Sanitize rendered HTML -->
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
    <!-- Syntax highlighting: highlight.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/atom-one-dark.min.css">
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>
    <!-- Math rendering: KaTeX -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Space Grotesk', sans-serif; }

        /* Markdown / chat content styling */
        .md-content table {
            border-collapse: collapse;
            margin: 1em 0;
            display: block;
            overflow-x: auto;
            max-width: 100%;
            font-size: 0.9em;
        }
        .md-content thead { background: #F5F0FF; }
        .md-content th, .md-content td {
            border: 1px solid #E5E7EB;
            padding: 0.5rem 0.75rem;
            text-align: left;
        }
        .md-content th { font-weight: 600; color: #4C1D95; }
        .md-content tr:nth-child(even) { background: #FAFAFA; }
        /* ── Code block: ChatGPT/Claude style with header bar ── */
        .md-content .code-block {
            border-radius: 0.6rem;
            overflow: hidden;
            margin: 0.85rem 0;
            background: #1F2937;
            border: 1px solid #374151;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .md-content .code-block-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.4rem 0.85rem;
            background: #111827;
            border-bottom: 1px solid #374151;
            font-size: 0.75rem;
            color: #9CA3AF;
            font-family: 'Space Grotesk', sans-serif;
        }
        .md-content .code-block-lang {
            font-weight: 500;
            text-transform: lowercase;
            letter-spacing: 0.02em;
        }
        .md-content .code-block-copy {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.6rem;
            color: #9CA3AF;
            border-radius: 0.35rem;
            cursor: pointer;
            font-size: 0.72rem;
            transition: all 0.15s;
            background: transparent;
            border: none;
            font-family: inherit;
        }
        .md-content .code-block-copy:hover {
            background: #1F2937;
            color: #F9FAFB;
        }
        .md-content .code-block-copy.copied { color: #34D399; }
        .md-content .code-block-copy svg { width: 13px; height: 13px; }
        .md-content .code-block pre {
            background: transparent;
            color: #F9FAFB;
            margin: 0;
            padding: 0.85rem 1rem;
            overflow-x: auto;
            font-size: 0.85em;
            line-height: 1.55;
            font-family: 'JetBrains Mono', 'SF Mono', Menlo, Consolas, monospace;
        }
        .md-content .code-block pre code {
            background: transparent;
            color: inherit;
            padding: 0;
            font-family: inherit;
        }
        /* Fallback for any pre without our wrapper (shouldn't happen, but safe) */
        .md-content > pre {
            background: #1F2937;
            color: #F9FAFB;
            border-radius: 0.5rem;
            padding: 0.85rem 1rem;
            margin: 0.85rem 0;
            overflow-x: auto;
            font-size: 0.85em;
            line-height: 1.55;
        }
        .md-content code {
            background: #F3E8FF;
            color: #6B21A8;
            padding: 0.1em 0.4em;
            border-radius: 0.25rem;
            font-size: 0.9em;
            font-family: 'JetBrains Mono', 'SF Mono', Menlo, Consolas, monospace;
        }
        .md-content h1, .md-content h2, .md-content h3, .md-content h4 {
            font-weight: 600;
            margin-top: 1em;
            margin-bottom: 0.4em;
            color: #1F2937;
        }
        .md-content h1 { font-size: 1.5em; }
        .md-content h2 { font-size: 1.3em; }
        .md-content h3 { font-size: 1.15em; }
        .md-content ul, .md-content ol { margin: 0.5em 0; padding-left: 1.5em; }
        .md-content ul { list-style: disc; }
        .md-content ol { list-style: decimal; }
        .md-content li { margin: 0.25em 0; }
        .md-content p { margin: 0.5em 0; line-height: 1.6; }
        .md-content blockquote {
            border-left: 3px solid #A78BFA;
            padding-left: 1em;
            color: #4B5563;
            margin: 0.75em 0;
            font-style: italic;
        }
        .md-content a { color: #7C3AED; text-decoration: underline; }
        .md-content strong { font-weight: 600; }
        .md-content em { font-style: italic; }
        .md-content hr { border: none; border-top: 1px solid #E5E7EB; margin: 1em 0; }
        .md-content .katex-display { margin: 0.75em 0; overflow-x: auto; overflow-y: hidden; }

        /* Video generation progress animations */
        @keyframes shimmer {
            from { background-position: 200% 0; }
            to { background-position: -200% 0; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes gridShift {
            from { background-position: 0 0; }
            to { background-position: 30px 30px; }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="h-screen overflow-hidden" style="background: linear-gradient(135deg, #FAFAFA 0%, #F5F0FF 50%, #EDE5FF 100%);">
    <div x-data="{
            sidebarExpanded: window.innerWidth >= 1024,
            mobileMenuOpen: false
         }"
         x-on:resize.window="if(window.innerWidth >= 1024) { mobileMenuOpen = false }"
         class="flex h-screen overflow-hidden">

        <!-- Mobile backdrop -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-black/30 z-30 lg:hidden">
        </div>

        <!-- Sidebar -->
        <x-sidebar :recentChats="$recentChats ?? []" />

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col h-full overflow-hidden transition-all duration-300"
              :class="sidebarExpanded ? 'lg:ml-64' : 'lg:ml-16'">
            <!-- Topbar -->
            <x-topbar />

            <!-- Page Content -->
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
