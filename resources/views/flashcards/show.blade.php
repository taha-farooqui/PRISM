@extends('layouts.app')

@section('title', $flashcardSet->title . ' - Prism AI')

@section('content')
<div x-data="flashcardStudy()" @keydown.left.window="prev()" @keydown.right.window="next()" @keydown.space.window.prevent="flip()" class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('flashcards.index') }}" class="text-sm text-gray-400 hover:text-purple-600 transition-colors mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Flashcards
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $flashcardSet->title }}</h1>
            @if($flashcardSet->description)
                <p class="text-sm text-gray-500 mt-1">{{ $flashcardSet->description }}</p>
            @endif
        </div>

        <!-- Progress -->
        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
            <span>Card <span x-text="currentIndex + 1"></span> of <span x-text="cards.length"></span></span>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">Press Space to flip, Arrows to navigate</span>
            </div>
        </div>
        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mb-6">
            <div class="h-full bg-[#7C3AED] rounded-full transition-all duration-500"
                 :style="'width: ' + ((currentIndex + 1) / cards.length * 100) + '%'"></div>
        </div>

        <!-- Flashcard -->
        <div class="flashcard-container mx-auto cursor-pointer mb-6" style="height: 300px;" @click="flip()">
            <div class="flashcard-inner relative w-full h-full" :class="isFlipped ? 'flipped' : ''">
                <!-- Front -->
                <div class="flashcard-front absolute inset-0 bg-white border-2 border-purple-200 rounded-2xl p-8 flex flex-col items-center justify-center shadow-sm">
                    <span class="text-xs font-medium text-purple-500 uppercase tracking-wider mb-4">Question</span>
                    <p class="text-xl text-center text-gray-800 font-medium leading-relaxed" x-text="cards[currentIndex].front"></p>
                    <span class="text-xs text-gray-300 mt-6">Click to reveal answer</span>
                </div>
                <!-- Back -->
                <div class="flashcard-back absolute inset-0 bg-gradient-to-br from-purple-600 to-purple-800 rounded-2xl p-8 flex flex-col items-center justify-center shadow-lg">
                    <span class="text-xs font-medium text-purple-200 uppercase tracking-wider mb-4">Answer</span>
                    <p class="text-xl text-center text-white font-medium leading-relaxed" x-text="cards[currentIndex].back"></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex items-center justify-between">
            <button @click="prev()"
                    :class="currentIndex > 0 ? 'text-gray-600 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl transition-colors text-sm font-medium">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Previous
            </button>

            <div class="flex items-center gap-1">
                <template x-for="(card, i) in cards" :key="i">
                    <button @click="currentIndex = i; isFlipped = false"
                            class="w-2.5 h-2.5 rounded-full transition-colors"
                            :class="i === currentIndex ? 'bg-purple-600' : 'bg-gray-200 hover:bg-gray-300'"></button>
                </template>
            </div>

            <button @click="next()"
                    :class="currentIndex < cards.length - 1 ? 'text-gray-600 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl transition-colors text-sm font-medium">
                Next
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </div>
</div>

<script>
    function flashcardStudy() {
        return {
            cards: @json($cards),
            currentIndex: 0,
            isFlipped: false,

            flip() {
                this.isFlipped = !this.isFlipped;
            },

            next() {
                if (this.currentIndex < this.cards.length - 1) {
                    this.currentIndex++;
                    this.isFlipped = false;
                }
            },

            prev() {
                if (this.currentIndex > 0) {
                    this.currentIndex--;
                    this.isFlipped = false;
                }
            }
        };
    }
</script>
@endsection
