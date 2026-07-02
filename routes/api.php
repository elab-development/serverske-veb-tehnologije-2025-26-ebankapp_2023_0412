<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CurrencyController;

Route::get('/status', function () {
    return response()->json(['status' => 'API is running']);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',  [AuthController::class, 'resetPassword']);

//KLIJENT
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('accounts',          [AccountController::class, 'index']);
    Route::post('accounts',         [AccountController::class, 'store']);
    Route::get('accounts/search',  [AccountController::class, 'search']);
    Route::get('accounts/{id}',     [AccountController::class, 'show']);
    Route::get('accounts/{id}/balance',       [AccountController::class, 'getBalance']);
    Route::get('accounts/{id}/transactions',  [TransactionController::class, 'byAccount']);

    Route::get('transactions',        [TransactionController::class, 'index']);
    Route::post('transactions',       [TransactionController::class, 'store']);
    Route::get('transactions/search', [TransactionController::class, 'search']);
    Route::get('transactions/spending-by-category', [TransactionController::class, 'spendingByCategory']);
    Route::get('transactions/{id}',   [TransactionController::class, 'show']);
    Route::post('transfer',           [TransactionController::class, 'transfer']);

    Route::post('users/{id}/change-password', [UserController::class, 'changePassword']);

    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
});

// MANAGER I ADMIN
Route::middleware(['auth:sanctum','isActive', 'isManager'])->group(function () {

    Route::put('accounts/{id}',    [AccountController::class, 'update']);
    Route::delete('accounts/{id}', [AccountController::class, 'destroy']);

    Route::put('transactions/{id}',    [TransactionController::class, 'update']);
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy']);
});

// SAMO ADMIN
Route::middleware(['auth:sanctum', 'isActive', 'isAdmin'])->group(function () {

    Route::apiResource('users', UserController::class);
    Route::patch('users/{id}/role',    [UserController::class, 'changeRole']);
    Route::patch('users/{id}/block',   [UserController::class, 'block']);
    Route::patch('users/{id}/unblock', [UserController::class, 'unblock']);
    Route::get('users/search',     [UserController::class, 'search']);
});

Route::get('/currencies/rates/{base?}',    [CurrencyController::class, 'rates']);
Route::get('/currencies/{code}/countries', [CurrencyController::class, 'countries']);