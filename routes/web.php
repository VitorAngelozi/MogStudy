<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

//auth routes
Route::get('/login', [AuthController::class, 'login']);
Route::post('loginsubmit', [AuthController::class, 'loginsubmit']);
Route::get('/logout', [AuthController::class, 'logout']);
