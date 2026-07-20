<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CirclePostController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudyGroupController;
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
    Route::get('/friend-search', [DashboardController::class, 'friendSearch'])->name('friend-search');
    Route::get('/study-subjects', [StudySubjectController::class, 'index'])->name('study-subjects.index');
    Route::post('/study-subjects', [StudySubjectController::class, 'store'])->name('study-subjects.store');
    Route::put('/study-subjects/{studySubject}', [StudySubjectController::class, 'update'])->name('study-subjects.update');
    Route::delete('/study-subjects/{studySubject}', [StudySubjectController::class, 'destroy'])->name('study-subjects.destroy');
    Route::post('/study-sessions', [StudySessionController::class, 'store'])->name('study-sessions.store');
    Route::post('/study-sessions/{studySession}/pause', [StudySessionController::class, 'pause'])->name('study-sessions.pause');
    Route::post('/study-sessions/{studySession}/resume', [StudySessionController::class, 'resume'])->name('study-sessions.resume');
    Route::post('/study-sessions/{studySession}/stop', [StudySessionController::class, 'stop'])->name('study-sessions.stop');
    Route::get('/study-groups', [StudyGroupController::class, 'index'])->name('study-groups.index');
    Route::get('/study-groups/create', [StudyGroupController::class, 'create'])->name('study-groups.create');
    Route::post('/study-groups', [StudyGroupController::class, 'store'])->name('study-groups.store');
    Route::post('/study-groups/join-by-code', [StudyGroupController::class, 'joinByCode'])->name('study-groups.join-by-code');
    Route::get('/study-groups/{studyGroup:code}', [StudyGroupController::class, 'show'])->name('study-groups.show');
    Route::patch('/study-groups/{studyGroup:code}', [StudyGroupController::class, 'update'])->name('study-groups.update');
    Route::post('/study-groups/{studyGroup:code}/join', [StudyGroupController::class, 'join'])->name('study-groups.join');
    Route::post('/study-groups/{studyGroup:code}/leave', [StudyGroupController::class, 'leave'])->name('study-groups.leave');
    Route::get('/study-groups/{studyGroup:code}/presence', [StudyGroupController::class, 'presence'])->name('study-groups.presence');
    Route::post('/study-groups/{studyGroup:code}/focus-rooms', [StudyGroupController::class, 'storeFocusRoom'])->name('study-groups.focus-rooms.store');
    Route::get('/study-groups/{studyGroup:code}/focus-rooms/{focusRoom}', [StudyGroupController::class, 'showFocusRoom'])->name('study-groups.focus-rooms.show');
    Route::patch('/study-groups/{studyGroup:code}/focus-rooms/{focusRoom}', [StudyGroupController::class, 'updateFocusRoom'])->name('study-groups.focus-rooms.update');
    Route::delete('/study-groups/{studyGroup:code}/focus-rooms/{focusRoom}', [StudyGroupController::class, 'destroyFocusRoom'])->name('study-groups.focus-rooms.destroy');
    Route::post('/study-groups/{studyGroup:code}/focus-rooms/{focusRoom}/start', [StudyGroupController::class, 'startFocusStudy'])->name('study-groups.focus-rooms.start');
    Route::post('/study-groups/{studyGroup:code}/focus-rooms/{focusRoom}/stop', [StudyGroupController::class, 'stopFocusStudy'])->name('study-groups.focus-rooms.stop');
    Route::get('/study-rooms', fn () => redirect()->route('study-groups.index'))->name('study-rooms.index');
    Route::post('/study-rooms', fn () => redirect()->route('study-groups.index'))->name('study-rooms.store');
    Route::get('/study-rooms/{studyRoom:code}', fn ($studyRoom) => redirect()->route('study-groups.show', $studyRoom))->name('study-rooms.show');
    Route::post('/study-rooms/{studyRoom:code}/join', fn ($studyRoom) => redirect()->route('study-groups.show', $studyRoom))->name('study-rooms.join');
    Route::post('/study-rooms/{studyRoom:code}/leave', fn ($studyRoom) => redirect()->route('study-groups.show', $studyRoom))->name('study-rooms.leave');
    Route::post('/study-rooms/{studyRoom:code}/close', fn ($studyRoom) => redirect()->route('study-groups.show', $studyRoom))->name('study-rooms.close');
    Route::post('/daily-logs', [DailyLogController::class, 'store'])->name('daily-logs.store');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/friendships/{user}', [FriendshipController::class, 'store'])->name('friendships.store');
    Route::post('/friendships/{friendship}/accept', [FriendshipController::class, 'accept'])->name('friendships.accept');
    Route::delete('/friendships/{friendship}', [FriendshipController::class, 'destroy'])->name('friendships.destroy');
    Route::post('/circle-posts', [CirclePostController::class, 'store'])->name('circle-posts.store');
    Route::post('/circle-posts/{post}/replies', [CirclePostController::class, 'reply'])->name('circle-posts.replies.store');
});

Route::get('/u/{user:username}', [ProfileController::class, 'show'])->name('profile.show');
