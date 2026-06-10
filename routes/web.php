<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Menu;
use App\Livewire\Admin\Tables;
use App\Livewire\Admin\Orders;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\Users;
use App\Livewire\Admin\Billing;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\Suppliers;
use App\Livewire\Admin\Inventory;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Kitchen\Login as KitchenLogin;
use App\Livewire\Waiter\Login as WaiterLogin;

Route::get('/', \App\Livewire\Home::class)->name('home');

Route::get('/admin/login', AdminLogin::class)->name('login');
Route::get('/kitchen/login', KitchenLogin::class)->name('kitchen.login');
Route::get('/waiter/login', WaiterLogin::class)->name('waiter.login');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::get('/admin/categories', Categories::class)->name('admin.categories');
    Route::get('/admin/menu', Menu::class)->name('admin.menu');
    Route::get('/admin/tables', Tables::class)->name('admin.tables');
    Route::get('/admin/orders', Orders::class)->name('admin.orders');
    Route::get('/admin/reports', Reports::class)->name('admin.reports');
    Route::get('/admin/users', Users::class)->name('admin.users');
    Route::get('/admin/billing', Billing::class)->name('admin.billing');
    Route::get('/admin/settings', Settings::class)->name('admin.settings');
    Route::get('/admin/audit-logs', AuditLogs::class)->name('admin.audit-logs');
    Route::get('/admin/suppliers', Suppliers::class)->name('admin.suppliers');
    Route::get('/admin/inventory', Inventory::class)->name('admin.inventory');
});

Route::middleware(['auth', 'role:kitchen'])->group(function () {
    Route::get('/kitchen/dashboard', \App\Livewire\Kitchen\Dashboard::class)->name('kitchen.dashboard');
});

Route::middleware(['auth', 'role:waiter'])->group(function () {
    Route::get('/waiter/dashboard', \App\Livewire\Waiter\Dashboard::class)->name('waiter.dashboard');
});

// Alias for admin.login if needed, but 'login' is used by Laravel middleware
Route::get('/admin/login-redirect', function() { return redirect()->route('login'); })->name('admin.login');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');
