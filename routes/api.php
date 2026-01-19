<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\iclockController;
use App\Http\Controllers\HRApiController;
use App\Http\Controllers\WebhookConfigController;
use App\Http\Controllers\ApiTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// // handshake
// Route::get('/iclock/cdata', [iclockController::class, 'handshake']);
// // request dari device
// Route::post('/iclock/cdata', [iclockController::class, 'receiveRecords']);

// Route::get('/iclock/test', [iclockController::class, 'test']);
// Route::get('/iclock/getrequest', [iclockController::class, 'getrequest']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// HR API Integration Routes
Route::prefix('v1/hr')->middleware(['api.token'])->group(function () {
    Route::get('/attendances', [HRApiController::class, 'getAttendances'])->name('hr.attendances');
    Route::get('/attendances/{id}', [HRApiController::class, 'getAttendanceById'])->name('hr.attendances.show');
    Route::get('/employees/{employee_id}/attendances', [HRApiController::class, 'getAttendancesByEmployee'])->name('hr.employees.attendances');
});

// Admin Routes for Webhook and Token Management
Route::prefix('v1/admin')->middleware(['api.token'])->group(function () {
    // Webhook Configuration Management
    Route::prefix('webhooks')->group(function () {
        Route::get('/', [WebhookConfigController::class, 'index']);
        Route::post('/', [WebhookConfigController::class, 'store']);
        Route::get('/{id}', [WebhookConfigController::class, 'show']);
        Route::put('/{id}', [WebhookConfigController::class, 'update']);
        Route::delete('/{id}', [WebhookConfigController::class, 'destroy']);
        Route::post('/{id}/test', [WebhookConfigController::class, 'test']);
    });

    // API Token Management
    Route::prefix('tokens')->group(function () {
        Route::get('/', [ApiTokenController::class, 'index']);
        Route::post('/', [ApiTokenController::class, 'store']);
        Route::put('/{id}/revoke', [ApiTokenController::class, 'revoke']);
    });
});
