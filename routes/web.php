<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;

//auth routes
Route::get('/login', [AuthController::class, 'login']);
Route::post('loginsubmit', [AuthController::class, 'loginsubmit']);

Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/home', [MainController::class, 'home']);
