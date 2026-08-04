<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AiConversationController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NoteController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PracticeController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/profile', [SettingsController::class, 'profile']);
        Route::patch('auth/password', [SettingsController::class, 'password']);
        Route::get('dashboard', DashboardController::class);
        Route::get('search', SearchController::class);
        Route::get('lookups', LookupController::class);
        Route::get('settings', [SettingsController::class, 'show']);
        Route::patch('settings', [SettingsController::class, 'update']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'read']);
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::get('ai/conversations', [AiConversationController::class, 'index']);
        Route::post('ai/conversations', [AiConversationController::class, 'store']);
        Route::get('ai/conversations/{conversation}', [AiConversationController::class, 'show']);
        Route::delete('ai/conversations/{conversation}', [AiConversationController::class, 'destroy']);
        Route::post('ai/conversations/{conversation}/messages', [AiConversationController::class, 'message']);
        Route::apiResource('contacts', ContactController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('contacts/{contact}/photo', [ContactController::class, 'photo']);
        Route::apiResource('companies', CompanyController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::apiResource('activities', ActivityController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('practices', PracticeController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::apiResource('goals', GoalController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::apiResource('documents', DocumentController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);
        Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
        Route::post('companies/{company}/contacts', [CompanyController::class, 'attachContact']);
        Route::delete('companies/{company}/contacts/{contact}', [CompanyController::class, 'detachContact']);
        Route::post('contacts/{contact}/notes', [NoteController::class, 'storeForContact']);
        Route::patch('notes/{note}', [NoteController::class, 'update']);
        Route::delete('notes/{note}', [NoteController::class, 'destroy']);
    });
});
