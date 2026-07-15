<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use App\Http\Middleware\CheckIfLogged;
use App\Http\Middleware\CheckIfNotLogged;

//auth routes

Route::middleware([CheckIfNotLogged::class])->group(function(){
	Route::get('/login', [AuthController::class, 'login']);
	Route::post('loginsubmit', [AuthController::class, 'loginsubmit']);
});



Route::middleware([CheckIfLogged::class])->group(function(){
	Route::post('/logout', [AuthController::class, 'logout']);
	Route::get('/home', [MainController::class, 'home']);
	Route::get('/', [MainController::class, 'index']);
	Route::get('/', [MainController::class, 'index']);
});
