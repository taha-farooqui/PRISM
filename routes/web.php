<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseGenerationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Guest Routes (only accessible when not logged in)
Route::middleware('guest')->group(function () {
    // Sign In
    Route::get('/signin', [AuthController::class, 'showSignIn'])->name('signin');
    Route::post('/signin', [AuthController::class, 'signIn'])->name('signin.post');

    // Sign Up
    Route::get('/signup', [AuthController::class, 'showSignUp'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signUp'])->name('signup.post');

    // Email Verification
    Route::get('/verify-email', [AuthController::class, 'showVerifyEmail'])->name('verify-email');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email.post');
    Route::post('/resend-code', [AuthController::class, 'resendCode'])->name('resend-code');

    // Google OAuth
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Authenticated Routes (only accessible when logged in)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Chat
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/regenerate', [ChatController::class, 'regenerate'])->name('chat.regenerate');

    // My Courses
    Route::get('/my-courses', [CourseController::class, 'index'])->name('my-courses');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    // Course Generation (must be before /courses/{course} to avoid route conflicts)
    Route::get('/courses/create', [CourseGenerationController::class, 'create'])->name('courses.create');
    Route::post('/courses/generate', [CourseGenerationController::class, 'generate'])->name('courses.generate');

    // Course Detail
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // Lesson View
    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');

    // Quizzes
    Route::post('/quizzes/generate', [QuizController::class, 'generate'])->name('quizzes.generate');
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/attempts/{attempt}', [QuizController::class, 'result'])->name('quizzes.result');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    // Flashcards
    Route::post('/flashcards/generate', [FlashcardController::class, 'generate'])->name('flashcards.generate');
    Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
    Route::get('/flashcards/{flashcardSet}', [FlashcardController::class, 'show'])->name('flashcards.show');
    Route::delete('/flashcards/{flashcardSet}', [FlashcardController::class, 'destroy'])->name('flashcards.destroy');

    // Videos
    Route::post('/videos/generate', [VideoController::class, 'generate'])->name('videos.generate');
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}/status', [VideoController::class, 'status'])->name('videos.status');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');

    // Resources (PDF uploads usable across the app)
    Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('/resources/list.json', [ResourceController::class, 'listJson'])->name('resources.list-json');
    Route::post('/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::get('/resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    // Progress
    Route::get('/progress', [ProgressController::class, 'show'])->name('progress');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Redirect /login to /signin (for Laravel's default redirect)
Route::get('/login', function () {
    return redirect()->route('signin');
})->name('login');
