<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthAny
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() || Auth::guard('admin')->check()) {
            return $next($request);
        }

        return redirect($request->is('admin/*') ? '/admin/login' : '/login');
    }
}
