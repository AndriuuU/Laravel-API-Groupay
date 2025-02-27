<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\ExpenseController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::get('/groups/{id}/members', [GroupController::class, 'members']);
    Route::post('/groups/{id}/members', [GroupController::class, 'addMember']);
    Route::delete('/groups/{groupId}/members/{memberId}', [GroupController::class, 'removeMember']);
    Route::get('/groups/{id}/balances', [GroupController::class, 'balances']);
    
    // Expenses
    Route::get('/groups/{groupId}/expenses', [ExpenseController::class, 'index']);
    Route::post('/groups/{groupId}/expenses', [ExpenseController::class, 'store']);
    Route::apiResource('expenses', ExpenseController::class)->only(['show', 'update', 'destroy']);
});
