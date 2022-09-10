<?php

use App\Http\Controllers\Api\Crons\LeadCronController;
use App\Http\Controllers\Api\Services\AmoCrm\AmoCrmAuthController;
use App\Http\Controllers\Api\Webhooks\LeadWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('webhook')->group(function () {
        Route::prefix('leads')->group(function () {
            Route::post('create', [LeadWebhookController::class, 'create']);
            Route::post('update', [LeadWebhookController::class, 'update']);
            Route::post('change-stage', [LeadWebhookController::class, 'changeStage']);
        });
    });
    Route::prefix('cron')->middleware('auth.amocrm')->group(function () {
        Route::get('leads', [LeadCronController::class, 'handle']);
    });
    Route::prefix('services')->group(function () {
        Route::prefix('amocrm')->group(function () {
            Route::prefix('auth')->group(function () {
                Route::get('signin', [AmoCrmAuthController::class, 'signin']);
                Route::get('signout', [AmoCrmAuthController::class, 'signout']);
            });
        });
    });
});
