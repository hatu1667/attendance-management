<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RegisterRequest;
use App\Models\User;

class RegisterController extends Controller
{
    public function show()
    {
        return view('register'); // 会員登録画面を表示
    }

    public function register(RegisterRequest $request)
    {
        // バリデーション済みデータ
        $data = $request->validated();

        // ユーザー作成
        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
        ]);

        // 自動ログイン
        Auth::login($user);

        // 登録後にプロフィール設定画面へ
        return redirect()->route('attendance.index');
    }
}
