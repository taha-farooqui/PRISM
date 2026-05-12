@extends('layouts.auth')

@section('title', 'Verify Email - Prism AI')

@section('content')
    <!-- Logo -->
    <div class="flex justify-center mb-6">
        <img src="{{ asset('assets/images/logo.svg') }}" alt="Prism AI" class="h-14 w-auto">
    </div>

    <!-- Heading -->
    <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">
        Verify Your Email
    </h1>

    <!-- Subtext -->
    <p class="text-center text-gray-500 text-sm mb-8">
        We've sent a 6-digit code to<br>
        <span class="font-medium text-gray-700">{{ $email }}</span>
    </p>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-600 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Error Messages -->
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- OTP Form -->
    <form action="{{ route('verify-email.post') }}" method="POST" id="otp-form">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <!-- OTP Input Boxes -->
        <div class="flex justify-center gap-3 mb-6">
            @for($i = 0; $i < 6; $i++)
                <input
                    type="text"
                    name="code[]"
                    maxlength="1"
                    class="otp-input w-12 h-14 text-center text-xl font-semibold bg-white border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition-colors"
                    data-index="{{ $i }}"
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]"
                >
            @endfor
        </div>

        <!-- Verify Button -->
        <button
            type="submit"
            class="w-full bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold rounded-lg py-3.5 transition-colors duration-200"
        >
            Verify Email
        </button>
    </form>

    <!-- Resend Code -->
    <div class="text-center mt-6">
        <p class="text-gray-500 text-sm mb-2">Didn't receive the code?</p>
        <form action="{{ route('resend-code') }}" method="POST" class="inline">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button
                type="submit"
                class="text-[#7C3AED] font-semibold text-sm hover:underline"
            >
                Resend Code
            </button>
        </form>
    </div>

    <!-- Back to Sign Up -->
    <p class="text-center text-sm text-gray-500 mt-6">
        <a href="{{ route('signup') }}" class="text-[#7C3AED] font-semibold hover:underline">
            ← Back to Sign Up
        </a>
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.otp-input');

            inputs.forEach((input, index) => {
                // Auto-focus next input on entry
                input.addEventListener('input', function(e) {
                    const value = e.target.value;

                    // Only allow numbers
                    if (!/^\d*$/.test(value)) {
                        e.target.value = '';
                        return;
                    }

                    // Move to next input
                    if (value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });

                // Handle backspace
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);

                    pastedData.split('').forEach((char, i) => {
                        if (inputs[i]) {
                            inputs[i].value = char;
                        }
                    });

                    // Focus last filled input or next empty one
                    const focusIndex = Math.min(pastedData.length, inputs.length - 1);
                    inputs[focusIndex].focus();
                });
            });

            // Auto-focus first input
            if (inputs[0]) {
                inputs[0].focus();
            }
        });
    </script>
@endsection
