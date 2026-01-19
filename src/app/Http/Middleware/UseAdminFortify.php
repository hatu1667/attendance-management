<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UseAdminFortify
{
    public function handle(Request $request, Closure $next)
    {
        config([
            'fortify.guard'     => 'admin',
            'fortify.passwords' => 'admins',
            'fortify.home'      => '/admin',
        ]);

        return $next($request);
    }
}
