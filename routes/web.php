<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\AbsensiSholatController;
use App\Http\Controllers\iclockController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

// Authentication Routes
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('devices', [DeviceController::class, 'Index'])->name('devices.index');
    Route::get('devices-log', [DeviceController::class, 'DeviceLog'])->name('devices.DeviceLog');
    Route::get('finger-log', [DeviceController::class, 'FingerLog'])->name('devices.FingerLog');
    Route::get('attendance', [DeviceController::class, 'Attendance'])->name('devices.Attendance');
    Route::get('api-docs', [DeviceController::class, 'ApiDocs'])->name('api.docs');
    
    // Admin Routes
    Route::get('admin/tokens', [AdminController::class, 'tokens'])->name('admin.tokens');
    Route::post('admin/tokens', [AdminController::class, 'createToken'])->name('admin.tokens.create');
    Route::patch('admin/tokens/{id}/revoke', [AdminController::class, 'revokeToken'])->name('admin.tokens.revoke');
    Route::delete('admin/tokens/{id}', [AdminController::class, 'deleteToken'])->name('admin.tokens.delete');

    Route::get('admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('admin/users', [AdminController::class, 'createUser'])->name('admin.users.create');
    Route::patch('admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::patch('admin/users/{id}/reset-password', [AdminController::class, 'resetPassword'])->name('admin.users.reset-password');
});

// iClock Device Routes (no auth - device communication)
Route::get('/iclock/cdata', [iclockController::class, 'handshake']);
Route::post('/iclock/cdata', [iclockController::class, 'receiveRecords']);
Route::get('/iclock/getrequest', [iclockController::class, 'getrequest']);
Route::get('/iclock/getrequest', [iclockController::class, 'getrequest']);

// Redirect root to devices (will redirect to login if not authenticated)
Route::get('/', function () {
    return redirect('devices');
});
