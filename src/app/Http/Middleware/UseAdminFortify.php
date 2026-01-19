<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UseAdminFortify
{
    public function handle(Request $request, Closure $next)
    {
        // Fortify が参照する設定を管理者用に差し替え
        config([
            'fortify.guard'     => 'admin',
            'fortify.passwords' => 'admins',
            'fortify.home'      => '/admin',  // ログイン後リダイレクト
        ]);

        return $next($request);
    }
}
