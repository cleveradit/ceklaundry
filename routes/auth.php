<?php

use App\Http\Controllers\Auth\AuthenticationController as AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'forgotForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
Route::middleware(['auth', 'tenant', 'business.access'])->group(function () {
    Route::get('/password/change', [AuthController::class, 'changeForm'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'change']);
});
