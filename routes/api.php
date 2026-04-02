<?php

use App\Http\Controllers\CertificatesReleaseController;
use App\Http\Controllers\CertificatesDownloadController;
use App\Http\Controllers\CertificatesSearchController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\EditionController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SpeakerController;
use App\Http\Controllers\Admin\CollaboratorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TalkController;
use App\Http\Controllers\Admin\ConfirmationController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// ── Public ──────────────────────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',  [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// ── Protected ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::get('/auth/me',       [AuthController::class, 'me']);
    Route::put('/auth/profile',  [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

    // Editions
    Route::apiResource('editions', EditionController::class);

    // Records
    Route::prefix('records')->name('records.')->group(function () {

        Route::apiResource('participants',  ParticipantController::class);
        Route::apiResource('speakers',      SpeakerController::class);
        Route::apiResource('collaborators', CollaboratorController::class);
        Route::apiResource('users',         UserController::class);

        Route::patch('participants/{participant}/confirm',
            [ParticipantController::class, 'confirm'])->name('participants.confirm');

        Route::patch('collaborators/{collaborator}/confirm',
            [CollaboratorController::class, 'confirm'])->name('collaborators.confirm');
    });

    // Talks
    Route::apiResource('talks', TalkController::class);
    Route::patch('talks/{talk}/confirm', [TalkController::class, 'confirm'])
        ->name('talks.confirm');

    // Confirmation / credenciamento list
    Route::prefix('confirmation')->name('confirmation.')->group(function () {
        Route::get('participants',  [ConfirmationController::class, 'participants'])
            ->name('participants');
        Route::get('collaborators', [ConfirmationController::class, 'collaborators'])
            ->name('collaborators');
        Route::get('talks',         [ConfirmationController::class, 'talks'])
            ->name('talks');
    });

    // Certificates
    Route::get('certificates/release', [CertificatesReleaseController::class, 'execute']);
    Route::get('certificates/{term}', [CertificatesSearchController::class, 'execute']);
    Route::get('certificates/{code}/download', [CertificatesDownloadController::class, 'execute']);
});
