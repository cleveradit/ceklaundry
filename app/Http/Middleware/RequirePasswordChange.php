<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
