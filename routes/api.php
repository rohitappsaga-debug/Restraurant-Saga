<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\PurchaseOrderController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    // General Resources
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('menu-items', MenuController::class);
    Route::apiResource('tables', TableController::class);
    Route::apiResource('reservations', ReservationController::class);
    Route::apiResource('notifications', NotificationController::class);
    Route::apiResource('recipes', RecipeController::class);
    Route::apiResource('payments', PaymentController::class);

    // Admin Sensitive Resources
    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('ingredients', IngredientController::class);
        Route::apiResource('reports', ReportController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('activity-logs', ActivityLogController::class);
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
    });
});
