<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CirclePostController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\StudySubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('landing');

Route::get('/home', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/study-subjects', [StudySubjectController::class, 'index'])->name('study-subjects.index');
    Route::post('/study-subjects', [StudySubjectController::class, 'store'])->name('study-subjects.store');
    Route::put('/study-subjects/{studySubject}', [StudySubjectController::class, 'update'])->name('study-subjects.update');
    Route::delete('/study-subjects/{studySubject}', [StudySubjectController::class, 'destroy'])->name('study-subjects.destroy');
    Route::post('/study-sessions', [StudySessionController::class, 'store'])->name('study-sessions.store');
    Route::post('/study-sessions/{studySession}/stop', [StudySessionController::class, 'stop'])->name('study-sessions.stop');
    Route::post('/daily-logs', [DailyLogController::class, 'store'])->name('daily-logs.store');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/friendships/{user}', [FriendshipController::class, 'store'])->name('friendships.store');
    Route::post('/friendships/{friendship}/accept', [FriendshipController::class, 'accept'])->name('friendships.accept');
    Route::delete('/friendships/{friendship}', [FriendshipController::class, 'destroy'])->name('friendships.destroy');
    Route::post('/circle-posts', [CirclePostController::class, 'store'])->name('circle-posts.store');
    Route::post('/circle-posts/{post}/replies', [CirclePostController::class, 'reply'])->name('circle-posts.replies.store');
});

Route::get('/u/{user:username}', [ProfileController::class, 'show'])->name('profile.show');
