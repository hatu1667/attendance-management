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

            Auth::guard('admin')->logout();

            $request->session()->regenerate();

            $request->session()->forget('url.intended');

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
