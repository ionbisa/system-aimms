<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\EmployeeBootController;
use App\Http\Controllers\EmployeeUniformController;
use App\Http\Controllers\ItemRequestController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/media/{path}', function (string $path) {
    abort_if(Str::contains($path, ['..', '\\']), 404);

    $file = storage_path('app/public/' . ltrim($path, '/'));

    abort_unless(is_file($file), 404);

    return response()->file($file, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*')->name('media.show');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /* DASHBOARD */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /* PROFILE */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* ASSET MANAGEMENT */
    Route::resource('asset-management', AssetController::class)
        ->parameters(['asset-management' => 'asset'])
        ->names('assets')
        ->except(['show']);

    /* PURCHASE ORDER */
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::get('/purchase-orders/export', [PurchaseOrderController::class, 'export'])->name('purchase-orders.export');
    Route::get('/purchase-orders/report/print', [PurchaseOrderController::class, 'printMonthly'])->name('purchase-orders.print-monthly');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::post('/purchase-orders/{purchaseOrder}/comment', [PurchaseOrderController::class, 'addComment'])->name('purchase-orders.comment');
    Route::post('/purchase-orders/{purchaseOrder}/expire', [PurchaseOrderController::class, 'expire'])->name('purchase-orders.expire');
    Route::post('/purchase-orders/{purchaseOrder}/realize', [PurchaseOrderController::class, 'realize'])->name('purchase-orders.realize');
    Route::post('/purchase-orders/{purchaseOrder}/complete', [PurchaseOrderController::class, 'complete'])->name('purchase-orders.complete');
    Route::post('/purchase-orders/{purchaseOrder}/approvals/{approval}', [PurchaseOrderController::class, 'approvalAction'])->name('purchase-orders.approvals.action');

    /* ITEM REQUESTS */
    Route::get('/item-requests', [ItemRequestController::class, 'index'])->name('item-requests.index');
    Route::get('/item-requests/create', [ItemRequestController::class, 'create'])->name('item-requests.create');
    Route::post('/item-requests', [ItemRequestController::class, 'store'])->name('item-requests.store');
    Route::get('/item-requests/export', [ItemRequestController::class, 'export'])->name('item-requests.export');
    Route::get('/item-requests/report/print', [ItemRequestController::class, 'printMonthly'])->name('item-requests.print-monthly');
    Route::get('/item-requests/{itemRequest}', [ItemRequestController::class, 'show'])->name('item-requests.show');
    Route::post('/item-requests/{itemRequest}/comment', [ItemRequestController::class, 'addComment'])->name('item-requests.comment');
    Route::post('/item-requests/{itemRequest}/expire', [ItemRequestController::class, 'expire'])->name('item-requests.expire');
    Route::post('/item-requests/{itemRequest}/realize', [ItemRequestController::class, 'realize'])->name('item-requests.realize');
    Route::post('/item-requests/{itemRequest}/approvals/{approval}', [ItemRequestController::class, 'approvalAction'])->name('item-requests.approvals.action');
    Route::get('/item-requests/{itemRequest}/print', [ItemRequestController::class, 'print'])->name('item-requests.print');

    /* REPORTING */
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/weekly', [ReportController::class, 'weekly'])->name('reports.weekly');
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/yearly', [ReportController::class, 'yearly'])->name('reports.yearly');
    Route::get('/reports/{preset}/excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    Route::get('/reports/{preset}/print', [ReportController::class, 'print'])->name('reports.print');

    /* USER MANAGEMENT */
    Route::get('/settings/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/settings/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/settings/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::patch('/settings/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('/settings/users/{user}/reset-password-default', [UserManagementController::class, 'resetToDefaultPassword'])->name('users.reset-password-default');
    Route::delete('/settings/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    /* STOCK */
    Route::get('/stock/on-stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('/stock/on-stock', [StockController::class, 'storeItem'])->name('stock.items.store');
    Route::put('/stock/on-stock/{stock}', [StockController::class, 'updateItem'])->name('stock.items.update');
    Route::delete('/stock/on-stock/{stock}', [StockController::class, 'destroyItem'])->name('stock.items.destroy');
    Route::get('/stock/inbound', [StockController::class, 'inbound'])->name('stock.inbound');
    Route::post('/stock/inbound', [StockController::class, 'storeInbound'])->name('stock.inbound.store');
    Route::get('/stock/outbound', [StockController::class, 'outbound'])->name('stock.outbound');
    Route::post('/stock/outbound', [StockController::class, 'storeOutbound'])->name('stock.outbound.store');

    /* APD KARYAWAN */
    Route::get('/apd-karyawan/sepatu', [EmployeeBootController::class, 'index'])->name('employee-boots.index');
    Route::post('/apd-karyawan/sepatu', [EmployeeBootController::class, 'store'])->name('employee-boots.store');
    Route::put('/apd-karyawan/sepatu/{employeeBoot}', [EmployeeBootController::class, 'update'])->name('employee-boots.update');
    Route::delete('/apd-karyawan/sepatu/{employeeBoot}', [EmployeeBootController::class, 'destroy'])->name('employee-boots.destroy');
    Route::get('/apd-karyawan/seragam-kerja', [EmployeeUniformController::class, 'index'])->name('employee-uniforms.index');
    Route::post('/apd-karyawan/seragam-kerja', [EmployeeUniformController::class, 'store'])->name('employee-uniforms.store');
    Route::put('/apd-karyawan/seragam-kerja/{employeeUniform}', [EmployeeUniformController::class, 'update'])->name('employee-uniforms.update');
    Route::delete('/apd-karyawan/seragam-kerja/{employeeUniform}', [EmployeeUniformController::class, 'destroy'])->name('employee-uniforms.destroy');

});
