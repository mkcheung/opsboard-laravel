<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

Route::prefix('auth')->group(function () {
  Route::post('/register', [AuthController::class, 'register']);
  Route::post('/login',    [AuthController::class, 'login']);
  Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
  // Projects
  Route::get('/projects', [ProjectController::class, 'index']);
  Route::post('/projects', [ProjectController::class, 'store']);
  Route::get('/projects/{id}', [ProjectController::class, 'show']);
  Route::put('/projects/{id}', [ProjectController::class, 'update']);
  Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

  // Tasks
  Route::get('/tasks', [TaskController::class, 'index']);
  Route::post('/tasks', [TaskController::class, 'store']);
  Route::get('/tasks/{id}', [TaskController::class, 'show']);
  Route::put('/tasks/{id}', [TaskController::class, 'update']);
  Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
});
