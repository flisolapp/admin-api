<?php

use App\Http\Controllers\Admin\EditionPlaceController;
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
use App\Http\Controllers\Admin\OrganizerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TalkController;
// use App\Http\Controllers\Admin\ConfirmationController;
use App\Http\Controllers\Admin\AttendanceController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// ── Public ──────────────────────────────────────────────────────────────
// Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',  [AuthController::class, 'login']);
// Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
// Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

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

        Route::apiResource('edition-places', EditionPlaceController::class);

        Route::apiResource('participants',  ParticipantController::class);
//        Route::patch('participants/{participant}/confirm',
//            [ParticipantController::class, 'confirm'])->name('participants.confirm');

        Route::get('talks/speaker-photo/{person}', [TalkController::class, 'speakerPhoto'])
            ->name('talks.speaker-photo');                        // → records.talks.speaker-photo
        Route::get('talks/{talk}/slide', [TalkController::class, 'slide'])
            ->name('talks.slide');                               // → records.talks.slide
        Route::apiResource('talks', TalkController::class);
        Route::patch('talks/{talk}/approve', [TalkController::class, 'approve'])
            ->name('talks.approve');
//        Route::patch('talks/{talk}/confirm', [TalkController::class, 'confirm'])
//            ->name('talks.confirm');

        Route::apiResource('speakers',      SpeakerController::class);

        Route::get('collaborators/metadata', [CollaboratorController::class, 'metadata'])
            ->name('collaborators.metadata');
        Route::apiResource('collaborators', CollaboratorController::class);
        Route::patch('collaborators/{collaborator}/approve',
            [CollaboratorController::class, 'approve'])->name('collaborators.approve');
//        Route::patch('collaborators/{collaborator}/confirm',
//            [CollaboratorController::class, 'confirm'])->name('collaborators.confirm');

        Route::apiResource('organizers',  OrganizerController::class);
        // Route::patch('organizers/{organizer}/confirm',
        //     [OrganizerController::class, 'confirm'])->name('organizers.confirm');

        Route::apiResource('users',         UserController::class);
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password');
    });

//    // Confirmation / credenciamento list
//    Route::prefix('confirmation')->name('confirmation.')->group(function () {
//        Route::get('participants',  [ConfirmationController::class, 'participants'])
//            ->name('participants');
//        Route::get('collaborators', [ConfirmationController::class, 'collaborators'])
//            ->name('collaborators');
//        Route::get('talks',         [ConfirmationController::class, 'talks'])
//            ->name('talks');
//    });

    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::patch('{kind}/{id}/check-in', [AttendanceController::class, 'toggleCheckIn'])
            ->name('check-in');
    });

    // Certificates
    Route::get('certificates/release', [CertificatesReleaseController::class, 'execute']);
    Route::get('certificates/{term}', [CertificatesSearchController::class, 'execute']);
    Route::get('certificates/{code}/download', [CertificatesDownloadController::class, 'execute']);
});
