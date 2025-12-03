<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebCustomRedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
                        DD('hello');

        if (Auth::guard('web')->check() && Auth::guard('web')->user()->status == 'active') {
            if (Auth::guard('web')->user()->hasRole('developer')) {
                return redirect()->intended(route('developer.dashboard', absolute: false));
            }elseif (Auth::guard('web')->user()->hasRole('admin') || Auth::guard('web')->user()->hasRole('staff')) {
                return redirect()->intended(route('admin.dashboard', absolute: false));
            }else{
                DD('SDF');
            }
        }
                DD('SASASAS');

        return redirect()->intended(route('home', absolute: false));
    }
}
