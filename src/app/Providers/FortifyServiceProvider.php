<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ルートに応じてビューを切替
        Fortify::loginView(function (Request $request) {
            return $request->is('admin/*')
                ? view('admin_login')         // 管理者ログイン
                : view('auth.login');         // 一般ユーザーログイン（既存）
        });

        // 管理者用の独自認証（/admin/* の時に有効化される想定）
        Fortify::authenticateUsing(function (Request $request) {
            if ($request->is('admin/*')) {
                $admin = Admin::where('email', $request->email)->first();
                if ($admin && Hash::check($request->password, $admin->password)) {
                    return $admin; // guard はミドルウェアで admin に差し替え済み
                }
                return null;
            }
            // それ以外（一般ユーザー）は Fortify の標準パイプラインに任せる
            return null;
        });
    }
}
