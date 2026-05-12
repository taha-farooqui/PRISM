@extends('layouts.app')

@section('title', $quiz->title . ' - Prism AI')

@section('content')
<div x-data="quizApp()" class="flex-1 overflow-y-auto px-4 md:px-8 py-8">
    <div class="max-w-3xl mx-auto">

        <!-- Quiz Header -->
        <div class="mb-6">
            <a href="{{ route('quizzes.index') }}" class="text-sm text-gray-400 hover:text-purple-600 transition-colors mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Quizzes
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
            @if($quiz->description)
                <p class="text-sm text-gray-500 mt-1">{{ $quiz->description }}</p>
            @endif
        </div>

        <!-- Progress Bar -->
        <div class="mb-8" x-show="!quizCompleted">
            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                <span>Question <span x-text="currentIndex + 1"></span> of <span x-text="totalQuestions"></span></span>
                <span x-text="Math.round(((currentIndex) / totalQuestions) * 100) + '%'"></span>
            </div>
            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-[#7C3AED] rounded-full transition-all duration-500"
                     :style="'width: ' + ((currentIndex) / totalQuestions * 100) + '%'"></div>
            </div>
        </div>

        <!-- Question Card -->
        <template x-if="!quizCompleted">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-5" x-text="questions[currentIndex].question"></h2>

                <div class="space-y-3">
                    <template x-for="option in ['A', 'B', 'C', 'D']" :key="option">
                        <button @click="if (!submitted) selectedAnswer = option"
                                :disabled="submitted"
                                class="w-full text-left px-4 py-3.5 rounded-xl border-2 transition-all duration-200 flex items-center gap-3"
                                :class="{
                                    'border-purple-500 bg-purple-50': selectedAnswer === option && !submitted,
                                    'border-green-500 bg-green-50': submitted && option === questions[currentIndex].correct_answer,
                                    'border-red-400 bg-red-50': submitted && selectedAnswer === option && option !== questions[currentIndex].correct_answer,
                                    'border-gray-200 hover:border-purple-300 hover:bg-purple-50/30': !submitted && selectedAnswer !== option,
                                    'border-gray-100 opacity-60': submitted && option !== questions[currentIndex].correct_answer && selectedAnswer !== option,
                                }">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-semibold shrink-0 transition-colors"
                                  :class="{
                                      'bg-purple-600 text-white': selectedAnswer === option && !submitted,
                                      'bg-green-600 text-white': submitted && option === questions[currentIndex].correct_answer,
                                      'bg-red-500 text-white': submitted && selectedAnswer === option && option !== questions[currentIndex].correct_answer,
                                      'bg-gray-100 text-gray-600': (!submitted && selectedAnswer !== option) || (submitted && option !== questions[currentIndex].correct_answer && selectedAnswer !== option),
                                  }"
                                  x-text="option"></span>
                            <span class="text-gray-700" x-text="questions[currentIndex]['option_' + option.toLowerCase()]"></span>
                        </button>
                    </template>
                </div>

                <!-- Explanation (shown after submit) -->
                <div x-show="submitted" x-transition class="mt-5 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                    <p class="text-sm text-blue-800">
                        <span class="font-semibold">Explanation:</span>
                        <span x-text="questions[currentIndex].explanation"></span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between mt-6">
                    <div></div>
                    <div class="flex items-center gap-3">
                        <button x-show="!submitted"
                                @click="checkAnswer()"
                                :disabled="!selectedAnswer"
                                class="px-6 py-2.5 text-sm font-medium text-white rounded-xl transition-all"
                                :class="selectedAnswer ? 'bg-[#7C3AED] hover:bg-[#6D28D9]' : 'bg-gray-300 cursor-not-allowed'">
                            Check Answer
                        </button>
                        <button x-show="submitted"
                                @click="nextQuestion()"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-[#7C3AED] hover:bg-[#6D28D9] rounded-xl transition-all">
                            <span x-text="currentIndex < totalQuestions - 1 ? 'Next Question' : 'See Results'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Results Screen -->
        <template x-if="quizCompleted">
            <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center"
                     :class="percentage >= 70 ? 'bg-green-100' : (percentage >= 40 ? 'bg-yellow-100' : 'bg-red-100')">
                    <span class="text-2xl font-bold"
                          :class="percentage >= 70 ? 'text-green-600' : (percentage >= 40 ? 'text-yellow-600' : 'text-red-600')"
                          x-text="percentage + '%'"></span>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-1">Quiz Complete!</h2>
                <p class="text-gray-500 mb-6">You scored <span class="font-semibold text-gray-700" x-text="score"></span> out of <span x-text="totalQuestions"></span> questions</p>

                <div class="flex items-center justify-center gap-3">
                    <button @click="retakeQuiz()" class="px-5 py-2.5 text-sm font-medium text-purple-600 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors">
                        Retake Quiz
                    </button>
                    <a href="{{ route('quizzes.index') }}" class="px-5 py-2.5 text-sm font-medium text-white bg-[#7C3AED] rounded-xl hover:bg-[#6D28D9] transition-colors">
                        Back to Quizzes
                    </a>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    function quizApp() {
        const questions = @json($questions);
        const quizId = {{ $quiz->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        return {
            questions: questions,
            currentIndex: 0,
            selectedAnswer: null,
            answers: {},
            submitted: false,
            quizCompleted: false,
            score: 0,
            totalQuestions: questions.length,
            percentage: 0,

            checkAnswer() {
                if (!this.selectedAnswer) return;
                this.submitted = true;
                this.answers[this.questions[this.currentIndex].id] = this.selectedAnswer;

                if (this.selectedAnswer === this.questions[this.currentIndex].correct_answer) {
                    this.score++;
                }
            },

            nextQuestion() {
                if (this.currentIndex < this.totalQuestions - 1) {
                    this.currentIndex++;
                    this.selectedAnswer = null;
                    this.submitted = false;
                } else {
                    this.quizCompleted = true;
                    this.percentage = Math.round((this.score / this.totalQuestions) * 100);
                    this.submitQuiz();
                }
            },

            async submitQuiz() {
                try {
                    await fetch('/quizzes/' + quizId + '/submit', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ answers: this.answers }),
                    });
                } catch (e) {}
            },

            retakeQuiz() {
                this.currentIndex = 0;
                this.selectedAnswer = null;
                this.answers = {};
                this.submitted = false;
                this.quizCompleted = false;
                this.score = 0;
                this.percentage = 0;
            }
        };
    }
</script>
@endsection
