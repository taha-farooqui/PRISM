<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Prism AI')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body class="bg-[#F0F0F5] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-6xl h-[90vh] max-h-[700px] bg-white rounded-2xl shadow-xl overflow-hidden flex">
        <!-- Left Half - Image (Hidden on mobile) -->
        <div class="hidden md:block w-1/2 p-2">
            <div class="w-full h-full rounded-l-xl overflow-hidden">
                <img
                    src="{{ asset('assets/images/left-image.jpg') }}"
                    alt="Authentication"
                    class="w-full h-full object-cover grayscale"
                >
            </div>
        </div>

        <!-- Right Half - Form -->
        <div class="w-full md:w-1/2 bg-[#F5F3FF] flex items-center justify-center p-8 md:p-12 rounded-2xl md:rounded-l-none md:rounded-r-xl relative">
            <div class="w-full max-w-[400px]">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
