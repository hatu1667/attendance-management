<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * スタッフ一覧（管理者）
     * GET /admin/staff/list
     * route: admin.staff.index
     */
    public function index(Request $request)
    {
        // 見た目固定したいので検索なし
        $staff = User::query()
            ->orderBy('name')
            ->paginate(10);

        return view('admin_staff_list', compact('staff'));
    }

    /**
     * スタッフ詳細（押したらスタッフ別勤怠一覧へ）
     * GET /admin/staff/{id}
     * route: admin.staff.show
     */
    public function show(int $id, Request $request)
    {
        // 一覧側で ym を持っているなら引き継ぐ（無ければ今月）
        $ym = $request->query('ym', now()->format('Y-m'));

        return redirect()->route('admin.attendance.staff', [
            'id' => $id,
            'ym' => $ym,
        ]);
    }
}
