<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordRecoveryVerificationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegistrationVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('register/verificar', [RegistrationVerificationController::class, 'show'])
        ->name('register.verify');

    Route::post('register/verificar', [RegistrationVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('register.verify.submit');

    Route::post('register/verificar/reenviar', [RegistrationVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('register.verify.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('forgot-password/verificar', [PasswordRecoveryVerificationController::class, 'show'])
        ->name('password.verify');

    Route::post('forgot-password/verificar', [PasswordRecoveryVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('password.verify.submit');

    Route::post('forgot-password/verificar/reenviar', [PasswordRecoveryVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('password.verify.resend');

    Route::get('reset-password', [PasswordRecoveryVerificationController::class, 'showReset'])
        ->name('password.reset');

    Route::post('reset-password', [PasswordRecoveryVerificationController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
