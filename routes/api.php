<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    
Route::apiResource('accounts', AccountController::class);
    
Route::get('accounts/{id}/balance', [AccountController::class, 'getBalance']);

Route::get('accounts/{id}/transactions', [AccountController::class, 'getTransactions']);
    
});
