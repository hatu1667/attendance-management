<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin_login');
    }

    public function login(AdminLoginRequest $request)
    {
        $cred = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($cred, false)) {
            // ✅ 管理者でログインできたら、一般ユーザー(web)は確実にログアウト
            Auth::guard('web')->logout();   // (= Auth::logout() と同じ)

            $request->session()->regenerate();
            $request->session()->forget('url.intended');

            return redirect('/admin/attendance/list');
        }

        return back()
            ->withErrors(['email' => 'ログイン情報が登録されていません'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
