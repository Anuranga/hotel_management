<?php

use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashierBillingController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\StaffOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Landing & Staff Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'home'])->name('home');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| A. Guest Self-Service Routes (Tamper-Proof Signed QR + Session Verified)
|--------------------------------------------------------------------------
*/
Route::get('/table/{qr_token}', [CustomerOrderController::class, 'entry'])
    ->name('table.entry');

Route::post('/order/place', [CustomerOrderController::class, 'placeOrder'])
    ->name('order.place');

Route::middleware('table.session')->group(function (): void {
    Route::post('/order/{order}/append', [CustomerOrderController::class, 'appendItems'])
        ->name('order.append');

    Route::get('/order/{order}/live-status', [CustomerOrderController::class, 'liveStatus'])
        ->name('order.live-status');
});

Route::get('/order/{order}/receipt', [CustomerOrderController::class, 'digitalReceipt'])
    ->name('order.receipt');

/*
|--------------------------------------------------------------------------
| B. Sales Assistant / Handheld Queue Routes (/staff/*)
|--------------------------------------------------------------------------
*/
Route::prefix('staff')
    ->name('staff.')
    ->middleware(['auth', 'role:staff,manager,admin'])
    ->group(function (): void {
        Route::get('/dashboard', [StaffOrderController::class, 'index'])->name('dashboard');
        Route::post('/orders/create', [StaffOrderController::class, 'createWalkInOrder'])->name('orders.create');
        Route::post('/orders/{order}/confirm', [StaffOrderController::class, 'confirmOrder'])->name('orders.confirm');
        Route::post('/orders/{order}/send-to-cashier', [StaffOrderController::class, 'completeAndSendToCashier'])->name('orders.send-to-cashier');
        Route::post('/batches/{batch}/deliver', [StaffOrderController::class, 'deliverBatch'])->name('batches.deliver');
        Route::post('/orders/{order}/append', [StaffOrderController::class, 'appendStaffItems'])->name('orders.append');
        Route::post('/items/{item}/cancel', [StaffOrderController::class, 'cancelItem'])->name('items.cancel');
    });

/*
|--------------------------------------------------------------------------
| C. Kitchen Display System Routes (/kitchen/*)
|--------------------------------------------------------------------------
*/
Route::prefix('kitchen')
    ->name('kitchen.')
    ->middleware(['auth', 'role:kitchen,manager,admin'])
    ->group(function (): void {
        Route::get('/display', [KitchenController::class, 'index'])->name('display');
        Route::post('/batches/{batch}/status', [KitchenController::class, 'updateBatchStatus'])->name('batches.status');
        Route::post('/items/{item}/status', [KitchenController::class, 'updateItemStatus'])->name('items.status');
    });

/*
|--------------------------------------------------------------------------
| D. Cashier Settlement & Billing Terminal Routes (/cashier/*)
|--------------------------------------------------------------------------
*/
Route::prefix('cashier')
    ->name('cashier.')
    ->middleware(['auth', 'role:cashier,manager,admin'])
    ->group(function (): void {
        Route::get('/billing', [CashierBillingController::class, 'index'])->name('billing');
        Route::post('/orders/{order}/discount', [CashierBillingController::class, 'applyDiscount'])->name('orders.discount');
        Route::post('/orders/{order}/append', [CashierBillingController::class, 'appendCashierItems'])->name('orders.append');
        Route::post('/orders/{order}/settle', [CashierBillingController::class, 'settle'])->name('orders.settle');
    });

/*
|--------------------------------------------------------------------------
| E. Management & Administrative Console Routes (/admin/* & /manager/*)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin,manager'])
    ->group(function (): void {
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
        Route::post('/categories', [AdminReportController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [AdminReportController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminReportController::class, 'destroyCategory'])->name('categories.destroy');

        Route::post('/menu-items', [AdminReportController::class, 'storeMenuItem'])->name('menu-items.store');
        Route::put('/menu-items/{menuItem}', [AdminReportController::class, 'updateMenuItem'])->name('menu-items.update');
        Route::post('/menu-items/{menuItem}/toggle-availability', [AdminReportController::class, 'toggleAvailability'])->name('menu-items.toggle');

        Route::post('/tables', [AdminReportController::class, 'storeTable'])->name('tables.store');
        Route::post('/tables/{table}/regenerate-qr', [AdminReportController::class, 'regenerateTableQr'])->name('tables.regenerate-qr');
    });

Route::prefix('manager')
    ->name('manager.')
    ->middleware(['auth', 'role:manager,admin'])
    ->group(function (): void {
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
    });
