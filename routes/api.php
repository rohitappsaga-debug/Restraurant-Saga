<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\IngredientController;
use App\Http\Controllers\Api\V1\KitchenController;
use App\Http\Controllers\Api\V1\MenuItemController;
use App\Http\Controllers\Api\V1\ModifierController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\RecipeController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TableController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Auth / profile
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        // Restaurant settings (readable by all roles — drives currency/tax/payment UI)
        Route::get('/settings', [SettingController::class, 'show']);

        // Push notification devices
        Route::post('/devices', [DeviceController::class, 'store']);
        Route::delete('/devices', [DeviceController::class, 'destroy']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

        // Menu browsing (all roles)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);

        // Waiter + admin + manager: tables and the full order lifecycle
        Route::middleware('role:waiter,admin,manager')->group(function () {
            Route::get('/tables', [TableController::class, 'index']);
            Route::get('/tables/{table}', [TableController::class, 'show']);
            Route::get('/tables/{table}/open-order', [TableController::class, 'openOrder']);
            Route::post('/tables/{table}/clean', [TableController::class, 'markCleaned']);
            Route::patch('/tables/{table}/status', [TableController::class, 'updateStatus']);

            Route::get('/orders', [OrderController::class, 'index']);
            Route::post('/orders', [OrderController::class, 'store']);
            Route::get('/orders/{order}', [OrderController::class, 'show']);
            Route::post('/orders/{order}/items', [OrderController::class, 'addItems']);
            Route::patch('/orders/{order}/items/{item}/serve', [OrderController::class, 'serveItem']);
            Route::post('/orders/{order}/serve-all', [OrderController::class, 'serveAll']);
            Route::get('/orders/{order}/bill', [OrderController::class, 'bill']);
            Route::post('/orders/{order}/discount', [OrderController::class, 'applyDiscount']);
            Route::post('/orders/{order}/payments', [OrderController::class, 'pay']);
            Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
            Route::post('/orders/{order}/hold', [OrderController::class, 'toggleHold']);

            Route::get('/reservations', [ReservationController::class, 'index']);
            Route::post('/reservations', [ReservationController::class, 'store']);
            Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
            Route::patch('/reservations/{reservation}', [ReservationController::class, 'update']);
            Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
            Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
        });

        // Kitchen + admin + manager: KOT queue and item transitions
        Route::middleware('role:kitchen,admin,manager')->group(function () {
            Route::get('/kitchen/queue', [KitchenController::class, 'queue']);
            Route::patch('/kitchen/items/{item}/status', [KitchenController::class, 'updateItemStatus']);
            Route::post('/kitchen/orders/{order}/dismiss', [KitchenController::class, 'dismiss']);
            Route::post('/kitchen/orders/{order}/force-close', [KitchenController::class, 'forceClose']);
            Route::post('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
        });

        // Admin + manager: management, reporting, back office
        Route::middleware('role:admin,manager')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'summary']);
            Route::get('/reports/sales', [ReportController::class, 'sales']);
            Route::get('/reports/analytics', [ReportController::class, 'analytics']);
            Route::get('/payments', [PaymentController::class, 'index']);
            Route::get('/activity-logs', [ActivityLogController::class, 'index']);

            Route::patch('/settings', [SettingController::class, 'update']);

            Route::post('/categories', [CategoryController::class, 'store']);
            Route::patch('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

            Route::post('/menu-items', [MenuItemController::class, 'store']);
            Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
            Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);
            Route::post('/menu-items/{menuItem}/image', [MenuItemController::class, 'uploadImage']);
            Route::post('/menu-items/{menuItem}/generate-image', [MenuItemController::class, 'generateImage']);

            // Menu item modifiers (add-ons)
            Route::post('/menu-items/{menuItem}/modifiers', [ModifierController::class, 'store']);
            Route::patch('/modifiers/{modifier}', [ModifierController::class, 'update']);
            Route::delete('/modifiers/{modifier}', [ModifierController::class, 'destroy']);

            Route::post('/tables', [TableController::class, 'store']);
            Route::post('/tables/bulk', [TableController::class, 'bulkStore']);
            Route::post('/tables/group', [TableController::class, 'group']);
            Route::post('/tables/ungroup', [TableController::class, 'ungroup']);
            Route::patch('/tables/{table}', [TableController::class, 'update']);
            Route::delete('/tables/{table}', [TableController::class, 'destroy']);

            Route::apiResource('suppliers', SupplierController::class);
            Route::apiResource('ingredients', IngredientController::class)->except('destroy');
            Route::post('/ingredients/{ingredient}/adjust-stock', [IngredientController::class, 'adjustStock']);
            Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy']);
            Route::apiResource('recipes', RecipeController::class);
            Route::apiResource('purchase-orders', PurchaseOrderController::class);
            Route::post('/purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'addItem']);
            Route::delete('/purchase-orders/{purchaseOrder}/items/{item}', [PurchaseOrderController::class, 'removeItem']);
            Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
            Route::apiResource('users', UserController::class);
        });
    });
});
