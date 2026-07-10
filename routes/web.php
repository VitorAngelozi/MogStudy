<?php
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

//auth routes
Route::get('/login/{value}', [AuthController::class, 'login']);
