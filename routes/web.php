<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\ModelPhotoController;
use App\Http\Controllers\PrivateIdentityDocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/registro', [RegistrationController::class, 'show'])->name('register.show');
    Route::post('/registro', [RegistrationController::class, 'store'])->name('register.store');
    Route::get('/registro/pendiente', [RegistrationController::class, 'pending'])->name('registration.pending');
    Route::get('/verificar-email/{token}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/verificar-email/reenviar', [EmailVerificationController::class, 'resend'])->name('verification.resend');
    Route::get('/login', [LoginController::class, 'show'])->name('login.show');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/password/forgot', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');
Route::get('/verificar-email', function (Request $request) {
    return view('auth.verification-status', ['email' => $request->user()->email]);
})->middleware('auth')->name('verification.notice');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/incomplete-profile', [AccountController::class, 'incompleteProfile'])
        ->name('account.incomplete-profile');
    Route::get('/account/photos', [ModelPhotoController::class, 'index'])->name('account.photos.index');
    Route::post('/account/photos', [ModelPhotoController::class, 'store'])->name('account.photos.store');
    Route::patch('/account/photos/order', [ModelPhotoController::class, 'order'])->name('account.photos.order');
    Route::post('/account/photos/{photo}/primary', [ModelPhotoController::class, 'setPrimary'])
        ->name('account.photos.primary');
    Route::post('/account/photos/{photo}/replace', [ModelPhotoController::class, 'replace'])
        ->name('account.photos.replace');
    Route::delete('/account/photos/{photo}', [ModelPhotoController::class, 'destroy'])
        ->name('account.photos.destroy');
    Route::get('/account/photos/{photo}/file/{variant}', [ModelPhotoController::class, 'file'])
        ->whereIn('variant', ['thumbnail', 'processed', 'public'])
        ->name('account.photos.file');
});

Route::get('/admin/private/model-photos/{photo}/versions/{version}/{variant}', [ModelPhotoController::class, 'adminFile'])
    ->middleware('auth')
    ->whereIn('variant', ['thumbnail', 'processed', 'public'])
    ->name('admin.model-photos.file');

Route::get('/cuenta/{user}', [AccountController::class, 'show'])
    ->middleware('auth')
    ->name('account.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/cuenta/{user}/identidad', [PrivateIdentityDocumentController::class, 'show'])
        ->name('identity.show');
    Route::post('/cuenta/{user}/identidad/documentos', [PrivateIdentityDocumentController::class, 'store'])
        ->name('identity.documents.store');
    Route::post('/cuenta/{user}/identidad/enviar', [PrivateIdentityDocumentController::class, 'submit'])
        ->name('identity.submit');
    Route::get('/cuenta/{user}/identidad/documentos/{document}/descargar', [PrivateIdentityDocumentController::class, 'download'])
        ->name('identity.documents.download');
});
