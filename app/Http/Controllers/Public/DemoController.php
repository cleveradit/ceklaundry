<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\DemoProvisioner;
use App\Services\DemoRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DemoController extends Controller
{
    public function store(Request $request, DemoRateLimiter $limiter, DemoProvisioner $provisioner)
    {
        abort_if($request->user(), 403, 'Keluar dari akun saat ini sebelum mencoba demo.');
        $limiter->check($request);
        $demo = $provisioner->provision();
        Auth::login($demo['owner'], false);
        $request->session()->regenerate();
        $request->session()->put('demo_pair', [
            'business_id' => $demo['business']->id,
            'owner_id' => $demo['owner']->id,
            'admin_id' => $demo['admin']->id,
        ]);
        Inertia::clearHistory();

        return redirect('/owner');
    }
}
