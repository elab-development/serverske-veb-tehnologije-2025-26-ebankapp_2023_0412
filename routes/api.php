<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;

Route::get('/status', function () {
    return response()->json(['status' => 'API is running']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('users', UserController::class);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::post('transfer', [TransactionController::class, 'transfer']);
    Route::get('transactions/search', [TransactionController::class, 'search']);
    Route::get('accounts/{id}/transactions', [TransactionController::class, 'byAccount']);
    Route::get('accounts/{id}/balance',      [AccountController::class, 'getBalance']);
    Route::patch('users/{id}/role', [UserController::class, 'changeRole']);
    Route::patch('/users/{id}/block', [UserController::class, 'block']);
    Route::patch('users/{id}/unblock', [UserController::class, 'unblock']);
    Route::post('users/{id}/change-password', [UserController::class, 'changePassword']);


    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
});
