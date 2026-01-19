<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use Illuminate\Http\Request;

class ApproveController extends Controller
{
    /**
     * 承認画面表示
     * GET /admin/stamp_correction_request/approve/{attendance_correct_request_id}
     */
    public function show(Request $request, int $attendance_correct_request_id)
    {
        $requestModel = AttendanceRequest::with(['attendance.breaks', 'attendance.user', 'user'])
            ->findOrFail($attendance_correct_request_id);

        $record = $requestModel->attendance;

        return view('admin_approve', [
            'requestModel' => $requestModel,
            'record'       => $record,
            'day'          => $record?->work_date,
        ]);
    }

    /**
     * 承認実行
     * POST /admin/stamp_correction_request/approve/{attendance_correct_request_id}
     */
    public function approve(Request $request, int $attendance_correct_request_id)
    {
        $requestModel = AttendanceRequest::findOrFail($attendance_correct_request_id);

        // pending の時だけ承認にする
        if ($requestModel->status === 'pending') {
            $requestModel->status = 'approved';
            $requestModel->save();
        }

        // 承認済み表示に切り替わった状態で詳細画面へ戻す
        return redirect()->route('admin.requests.approve.show', [
            'attendance_correct_request_id' => $requestModel->id,
        ]);
    }
}
