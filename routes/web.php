<?php

use App\Http\Controllers\Developer\BusinessController;
use App\Http\Controllers\Owner\AdminController;
use App\Http\Controllers\Owner\BranchController;
use App\Http\Controllers\Owner\BranchServiceController;
use App\Http\Controllers\Owner\MasterServiceController;
use App\Http\Controllers\Owner\MasterSyncController;
use App\Models\Branch;
use App\Models\MasterService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return response()->view('public.home')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'tenant', 'business.access', 'password.changed'])->group(function () {
    Route::middleware('role:developer')->group(function () {
        Route::get('/dev', [BusinessController::class, 'index']);
        Route::post('/dev/businesses', [BusinessController::class, 'store']);
        Route::put('/dev/businesses/{id}', [BusinessController::class, 'update'])->whereNumber('id');
        Route::post('/dev/businesses/{id}/reset-owner', [BusinessController::class, 'resetOwner'])->whereNumber('id');
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
    });
    Route::get('/app', function () {
        $user = auth()->user();
        if ($user->role === 'owner') {
            return redirect('/owner');
        }

        return Inertia::render('AdminHome', ['branch' => Branch::query()->findOrFail($user->branch_id)->only(['nama', 'alamat', 'telepon']), 'services' => Service::query()->where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'harga', 'satuan', 'durasi_jam'])]);
    })->middleware('role:admin,owner');
});
