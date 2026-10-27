<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountNotificationController;
use App\Http\Controllers\AccountProfileController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\LegalAccessController;
use App\Http\Controllers\ModelPhotoController;
use App\Http\Controllers\ModelVideoController;
use App\Http\Controllers\PrivateIdentityDocumentController;
use App\Http\Controllers\PublicModelPhotoController;
use App\Http\Controllers\PublicModelProfileController;
use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\RequireLegalAcceptance;
use App\Services\PublicModelHomepageProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/aviso-legal', [LegalAccessController::class, 'show'])->name('legal.show');
Route::post('/aviso-legal', [LegalAccessController::class, 'decide'])->name('legal.decide');
Route::get('/acceso-denegado', [LegalAccessController::class, 'denied'])->name('legal.denied');

// Keep all public content endpoints (including photo delivery) inside this group.
Route::middleware(RequireLegalAcceptance::class)->group(function (): void {
    Route::view('/terminos-y-condiciones', 'legal.terms')->name('terms.show');
    Route::view('/politicas-del-sitio', 'legal.privacy')->name('privacy.show');

    Route::get('/', function (PublicModelHomepageProfiles $profiles) {
        $profilesByProvince = $profiles->groupedByProvince();
        $publicProfiles = array_merge(...array_map(fn (array $group) => $group['profiles'], $profilesByProvince) ?: [[]]);

        return view('welcome', ['publicProfiles' => $publicProfiles, 'profilesByProvince' => $profilesByProvince]);
    })->name('home');

    Route::get('/modelos/fotos/{publicToken}', PublicModelPhotoController::class)
        ->where('publicToken', '.*')->name('public.models.photos.show');
    Route::get('/modelos/{slug}', PublicModelProfileController::class)->name('public.models.show');

});

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

Route::get('/account/pending-review', [AccountController::class, 'pendingReview'])
    ->middleware(['auth', 'verified'])->name('account.pending-review');

Route::middleware(['auth', 'verified', EnsureProfileIsComplete::class.':onboarding'])->group(function (): void {
    Route::get('/account/incomplete-profile', [AccountController::class, 'incompleteProfile'])
        ->name('account.incomplete-profile');
    Route::post('/account/incomplete-profile', [AccountController::class, 'completeProfile'])
        ->name('account.complete-profile');
});

Route::middleware(['auth', 'verified', EnsureProfileIsComplete::class])->group(function (): void {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/profile', [AccountProfileController::class, 'edit'])->name('account.profile.edit');
    Route::post('/account/notifications/read', [AccountNotificationController::class, 'markAllRead'])
        ->name('account.notifications.read');
    Route::get('/account/services', [AccountProfileController::class, 'services'])->name('account.services.edit');
    Route::patch('/account/profile', [AccountProfileController::class, 'update'])->name('account.profile.update');
    Route::patch('/account/profile/publication-type', [AccountProfileController::class, 'changePublicationType'])
        ->name('account.profile.publication-type.update');
    Route::patch('/account/profile/services', [AccountProfileController::class, 'updateServices'])
        ->name('account.profile.services.update');
    Route::post('/account/profile/bios', [AccountProfileController::class, 'storeBio'])
        ->name('account.profile.bios.store');
    Route::match(['put', 'patch'], '/account/profile/bios/{bio}', [AccountProfileController::class, 'updateBio'])
        ->name('account.profile.bios.update');
    Route::post('/account/profile/physical-revisions', [AccountProfileController::class, 'storePhysicalRevision'])
        ->name('account.profile.physical-revisions.store');
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

Route::middleware('auth')->group(function (): void {
    Route::post('/admin/model-profile-physical-revisions/{revision}/approve', [AccountProfileController::class, 'approvePhysicalRevision'])
        ->name('admin.model-profile-physical-revisions.approve');
    Route::post('/admin/model-profile-physical-revisions/{revision}/reject', [AccountProfileController::class, 'rejectPhysicalRevision'])
        ->name('admin.model-profile-physical-revisions.reject');
});

Route::get('/admin/private/model-photos/{photo}/versions/{version}/{variant}', [ModelPhotoController::class, 'adminFile'])
    ->middleware('auth')
    ->whereIn('variant', ['thumbnail', 'processed', 'public'])
    ->name('admin.model-photos.file');

Route::get('/cuenta/{user}', [AccountController::class, 'show'])
    ->middleware(['auth', 'verified', EnsureProfileIsComplete::class])
    ->name('account.show');

Route::middleware(['auth', 'verified', EnsureProfileIsComplete::class])->group(function (): void {
    Route::get('/cuenta/{user}/identidad', [PrivateIdentityDocumentController::class, 'show'])
        ->name('identity.show');
    Route::post('/cuenta/{user}/identidad/documentos', [PrivateIdentityDocumentController::class, 'store'])
        ->name('identity.documents.store');
    Route::post('/cuenta/{user}/identidad/enviar', [PrivateIdentityDocumentController::class, 'submit'])
        ->name('identity.submit');
    Route::get('/cuenta/{user}/identidad/documentos/{document}/descargar', [PrivateIdentityDocumentController::class, 'download'])
        ->name('identity.documents.download');
});

Route::middleware(['auth', 'verified', EnsureProfileIsComplete::class])->group(function (): void {
    Route::get('/account/videos', [ModelVideoController::class, 'index'])->name('account.videos.index');
    Route::post('/account/videos', [ModelVideoController::class, 'store'])->middleware('throttle:10,1')->name('account.videos.store');
    Route::delete('/account/videos/{video}', [ModelVideoController::class, 'destroy'])->name('account.videos.destroy');
    Route::post('/account/videos/{video}/retry', [ModelVideoController::class, 'retry'])->middleware('throttle:10,1')->name('account.videos.retry');
});
Route::get('/account/videos/{video}/file/{variant}', [ModelVideoController::class, 'file'])
    ->middleware(['auth', 'verified', EnsureProfileIsComplete::class])->whereIn('variant', ['video', 'poster'])->name('account.videos.file');
Route::get('/admin/private/model-videos/{video}/{variant}', [ModelVideoController::class, 'adminFile'])
    ->middleware(['auth', 'verified'])->whereIn('variant', ['video', 'poster'])->name('admin.model-videos.file');
Route::get('/model-videos/{token}/{variant}', [ModelVideoController::class, 'publicFile'])
    ->middleware(RequireLegalAcceptance::class)->whereUuid('token')->whereIn('variant', ['video', 'poster'])->name('public.models.videos.file');
