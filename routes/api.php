<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/sanctum/register', [AuthController::class, 'register']);

Route::post('/sanctum/token', [AuthController::class, 'login']);

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
