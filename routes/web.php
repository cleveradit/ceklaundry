<?php

use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\CustomerMergeController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ManualNotificationController;
use App\Http\Controllers\App\PaymentController;
use App\Http\Controllers\App\QuoteController;
use App\Http\Controllers\App\ReceiptPrintController;
use App\Http\Controllers\App\TransactionController;
use App\Http\Controllers\App\TransactionStatusController;
use App\Http\Controllers\Developer\BusinessController;
use App\Http\Controllers\Developer\NotificationConfigController;
use App\Http\Controllers\Owner\AdminController;
use App\Http\Controllers\Owner\BranchController;
use App\Http\Controllers\Owner\BranchServiceController;
use App\Http\Controllers\Owner\MasterServiceController;
use App\Http\Controllers\Owner\MasterSyncController;
use App\Http\Controllers\Owner\NotificationSettingController;
use App\Http\Controllers\Owner\PaymentSettingController;
use App\Http\Controllers\Public\ReceiptController;
use App\Http\Controllers\Public\ReceiptEmailController;
use App\Models\Branch;
use App\Models\MasterService;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return response()->view('public.home')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
});
Route::get('/check', [ReceiptController::class, 'search']);
Route::get('/t/{kodeResi}', [ReceiptController::class, 'show']);
Route::get('/t/{kodeResi}/print', [ReceiptController::class, 'print']);
Route::post('/t/{kodeResi}/email', [ReceiptEmailController::class, 'request']);
Route::get('/t/{kodeResi}/email/confirm', [ReceiptEmailController::class, 'show'])->name('receipt.email.confirm');
Route::post('/t/{kodeResi}/email/confirm', [ReceiptEmailController::class, 'confirm']);

require __DIR__.'/auth.php';

Route::middleware(['auth', 'tenant', 'business.access', 'password.changed'])->group(function () {
    Route::middleware('role:developer')->group(function () {
        Route::get('/dev', [BusinessController::class, 'index']);
        Route::post('/dev/businesses', [BusinessController::class, 'store']);
        Route::put('/dev/businesses/{id}', [BusinessController::class, 'update'])->whereNumber('id');
        Route::post('/dev/businesses/{id}/reset-owner', [BusinessController::class, 'resetOwner'])->whereNumber('id');
        Route::get('/dev/businesses/{id}/notifications', [NotificationConfigController::class, 'edit'])->whereNumber('id');
        Route::put('/dev/businesses/{id}/notifications', [NotificationConfigController::class, 'update'])->whereNumber('id');
    });
    Route::middleware('role:owner')->group(function () {
        Route::get('/owner', fn () => Inertia::render('Dashboard', ['branchCount' => Branch::query()->count(), 'serviceCount' => MasterService::query()->count(), 'adminCount' => User::query()->where('business_id', auth()->user()->business_id)->where('role', 'admin')->count()]));
        foreach (['branches' => BranchController::class, 'admins' => AdminController::class, 'masters' => MasterServiceController::class] as $path => $controller) {
            Route::get('/owner/'.$path, [$controller, 'index']);
            Route::post('/owner/'.$path, [$controller, 'store']);
            Route::put('/owner/'.$path.'/{id}', [$controller, 'update'])->whereNumber('id');
        }
        Route::post('/owner/admins/{id}/reset', [AdminController::class, 'reset'])->whereNumber('id');
        Route::get('/owner/branches/{branch}/services', [BranchServiceController::class, 'index'])->whereNumber('branch');
        Route::post('/owner/branches/{branch}/services', [BranchServiceController::class, 'store'])->whereNumber('branch');
        Route::put('/owner/branches/{branch}/services/{id}', [BranchServiceController::class, 'update'])->whereNumber(['branch', 'id']);
        Route::get('/owner/sync', [MasterSyncController::class, 'index']);
        Route::post('/owner/sync/preview', [MasterSyncController::class, 'preview']);
        Route::post('/owner/sync', [MasterSyncController::class, 'apply']);
        Route::get('/owner/settings/payment', [PaymentSettingController::class, 'index']);
        Route::put('/owner/settings/payment', [PaymentSettingController::class, 'update']);
        Route::get('/owner/settings/notifications', [NotificationSettingController::class, 'index']);
        Route::put('/owner/settings/notifications', [NotificationSettingController::class, 'update']);
    });
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/app', [DashboardController::class, 'index']);
        Route::get('/app/customers', [CustomerController::class, 'index']);
        Route::get('/app/customers/lookup', [CustomerController::class, 'lookup']);
        Route::post('/app/customers', [CustomerController::class, 'store']);
        Route::put('/app/customers/{id}', [CustomerController::class, 'update'])->whereNumber('id');
        Route::post('/app/customers/merge', [CustomerMergeController::class, 'store']);
        Route::post('/app/quote', [QuoteController::class, 'store']);
        Route::get('/app/transactions', [TransactionController::class, 'index']);
        Route::get('/app/transactions/create', [TransactionController::class, 'create']);
        Route::post('/app/transactions', [TransactionController::class, 'store']);
        Route::get('/app/transactions/{id}/edit', [TransactionController::class, 'edit'])->whereNumber('id');
        Route::get('/app/transactions/{id}', [TransactionController::class, 'show'])->whereNumber('id');
        Route::put('/app/transactions/{id}', [TransactionController::class, 'update'])->whereNumber('id');
        Route::post('/app/transactions/{id}/payments', [PaymentController::class, 'store'])->whereNumber('id');
        Route::post('/app/transactions/{id}/status', [TransactionStatusController::class, 'store'])->whereNumber('id');
        Route::get('/app/transactions/{id}/print', [ReceiptPrintController::class, 'show'])->whereNumber('id');
        Route::post('/app/transactions/{id}/notifications/email', [ManualNotificationController::class, 'email'])->whereNumber('id');
        Route::post('/app/transactions/{id}/notifications/whatsapp', [ManualNotificationController::class, 'whatsapp'])->whereNumber('id');
    });
});
