<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BodyMeasurementController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ClassBookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\GymClassController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MembershipPackageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PTBookingController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\WorkoutSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------------------
    // Public Authentication Endpoints
    // -------------------------------------------------------------------------
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // -------------------------------------------------------------------------
    // Public Membership Packages
    // -------------------------------------------------------------------------
    Route::get('packages', [MembershipPackageController::class, 'index']);
    Route::get('packages/{id}', [MembershipPackageController::class, 'show']);

    // -------------------------------------------------------------------------
    // Public Webhook Endpoints
    // -------------------------------------------------------------------------
    Route::post('payments/webhook', [PaymentController::class, 'webhook']);

    // -------------------------------------------------------------------------
    // Protected Endpoints (Requires Sanctum Authentication)
    // -------------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // --- Auth User Profile & Password ---
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('password', [AuthController::class, 'changePassword']);
        });

        // --- Dashboard / Home ---
        Route::get('dashboard', [DashboardController::class, 'index']);

        // --- Subscriptions & Dynamic QR ---
        Route::prefix('subscriptions')->group(function () {
            Route::get('current', [SubscriptionController::class, 'current']);
            Route::get('history', [SubscriptionController::class, 'history']);
            Route::get('dynamic-qr', [SubscriptionController::class, 'dynamicQr']);
            Route::post('/', [SubscriptionController::class, 'subscribe'])->middleware('role:admin,manager,staff');
            Route::post('freeze', [SubscriptionController::class, 'freeze']);
            Route::post('{id}/unfreeze', [SubscriptionController::class, 'unfreeze']);
        });

        // --- Check-in & Turnstile Access ---
        Route::prefix('check-in')->middleware('throttle:checkin')->group(function () {
            Route::post('/', [CheckInController::class, 'checkIn']);
            Route::post('checkout', [CheckInController::class, 'checkOut']);
            Route::get('history', [CheckInController::class, 'history']);
            Route::get('today', [CheckInController::class, 'today'])->middleware('role:admin,manager,staff');
        });

        // --- Exercise Library ---
        Route::prefix('exercises')->group(function () {
            Route::get('/', [ExerciseController::class, 'index']);
            Route::get('muscle-groups', [ExerciseController::class, 'muscleGroups']);
            Route::get('{id}', [ExerciseController::class, 'show']);
        });

        // --- Workout Sessions & Logging ---
        Route::prefix('workouts')->group(function () {
            Route::post('start', [WorkoutSessionController::class, 'start']);
            Route::post('{id}/log-set', [WorkoutSessionController::class, 'logSet']);
            Route::post('{id}/complete', [WorkoutSessionController::class, 'complete']);
            Route::get('history', [WorkoutSessionController::class, 'history']);
            Route::get('{id}', [WorkoutSessionController::class, 'show']);
        });

        // --- Body Measurements & InBody Tracking ---
        Route::prefix('measurements')->group(function () {
            Route::get('/', [BodyMeasurementController::class, 'index']);
            Route::post('/', [BodyMeasurementController::class, 'store']);
            Route::get('chart', [BodyMeasurementController::class, 'chart']);
            Route::get('{id}', [BodyMeasurementController::class, 'show']);
        });

        // --- Group Classes & Schedules ---
        Route::prefix('classes')->group(function () {
            Route::get('/', [GymClassController::class, 'index']);
            Route::get('schedules', [GymClassController::class, 'schedules']);
            Route::get('{id}', [GymClassController::class, 'show']);
        });

        // --- Class Bookings ---
        Route::prefix('class-bookings')->group(function () {
            Route::get('my-bookings', [ClassBookingController::class, 'myBookings']);
            Route::post('/', [ClassBookingController::class, 'book']);
            Route::post('{scheduleId}/cancel', [ClassBookingController::class, 'cancel']);
        });

        // --- Trainers ---
        Route::prefix('trainers')->group(function () {
            Route::get('/', [TrainerController::class, 'index']);
            Route::get('{id}', [TrainerController::class, 'show']);
            Route::get('{id}/availabilities', [TrainerController::class, 'availabilities']);
        });

        // --- PT Bookings ---
        Route::prefix('pt-bookings')->group(function () {
            Route::get('my-bookings', [PTBookingController::class, 'myBookings']);
            Route::post('/', [PTBookingController::class, 'book']);
            Route::post('{id}/cancel', [PTBookingController::class, 'cancel']);
            Route::post('{id}/review', [PTBookingController::class, 'review']);
        });

        // --- Payments ---
        Route::prefix('payments')->middleware('throttle:payments')->group(function () {
            Route::post('create', [PaymentController::class, 'createPayment']);
            Route::get('history', [PaymentController::class, 'history']);
        });

        // --- Notifications ---
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
        });

        // --- Media (Upload, Download, Streaming & Signed URLs) ---
        Route::prefix('media')->group(function () {
            Route::get('/', [MediaController::class, 'index']);
            Route::get('{id}', [MediaController::class, 'show'])->whereNumber('id');
            Route::post('upload', [MediaController::class, 'upload']);
            Route::post('upload-inbody-batch', [MediaController::class, 'uploadInBodyBatch']);
            Route::get('{id}/download', [MediaController::class, 'download'])->name('api.v1.media.download')->whereNumber('id');
            Route::get('{id}/stream', [MediaController::class, 'stream'])->name('api.v1.media.stream')->whereNumber('id');
            Route::get('{id}/signed-url', [MediaController::class, 'signedUrl'])->whereNumber('id');
            Route::delete('{id}', [MediaController::class, 'destroy'])->whereNumber('id');
        });
    });
});
