<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InstructionController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



// عرض كل السيارات
Route::get('/cars', [CarController::class, 'index']);

// عرض سيارة واحدة
Route::get('/cars/{id}', [CarController::class, 'show']);

// للأدمن
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cars', [CarController::class, 'store']);
    Route::put('/cars/{id}', [CarController::class, 'update']);
    Route::delete('/cars/{id}', [CarController::class, 'destroy']);
});


// إنشاء حجز
Route::middleware('auth:sanctum')->post('/bookings', [BookingController::class, 'store']);

// حجوزات المستخدم
Route::middleware('auth:sanctum')->get('/my-bookings', [BookingController::class, 'myBookings']);

// ADMIN
// Route::middleware('auth:sanctum')->get('/bookings', [BookingController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']); // للأدمن
    Route::put('/bookings/{id}/approve', [BookingController::class, 'approve']);
    Route::put('/bookings/{id}/reject', [BookingController::class, 'reject']);
});
Route::middleware('auth:sanctum')->get('/bookings/{id}', [BookingController::class, 'show']);
Route::get('/admin/pending-bookings-count', function () {
    return response()->json([
        'count' => \App\Models\Booking::where('status', 'pending')->count()
    ]);
});



Route::middleware('auth:sanctum')->group(function () {

    Route::get('/contracts', [ContractController::class, 'index']);
    Route::get('/contracts/{id}', [ContractController::class, 'show']);
    Route::get('/my-contracts', [ContractController::class, 'myContracts']);

});
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/instructions', [InstructionController::class, 'index']);

    Route::post('/instructions', [InstructionController::class, 'store']);

    Route::put('/instructions/{id}', [InstructionController::class, 'update']);

    Route::delete('/instructions/{id}', [InstructionController::class, 'destroy']);
});