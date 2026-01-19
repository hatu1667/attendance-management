<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function login(LoginRequest $request)
    {
        $cred = $request->validated();

        if (Auth::guard('web')->attempt($cred, $request->boolean('remember'))) {

            // 念のため：管理者ログイン状態を切る（同一ブラウザで混ざるの防止）
            Auth::guard('admin')->logout();

            $request->session()->regenerate();

            // ✅ これが重要：以前の intended を捨てる（/admin/login などに飛ばない）
            $request->session()->forget('url.intended');

            // ✅ 一般ユーザーは必ず勤怠へ
            return redirect()->route('attendance.index');
        }

        return back()
            ->withErrors(['email' => 'ログイン情報が登録されていません'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.show');
    }
}
