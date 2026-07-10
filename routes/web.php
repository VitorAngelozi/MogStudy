<?php
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get("/login", [UserController::class, "login"]);