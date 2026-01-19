<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AttendanceList;
use App\Models\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /**
     * 申請一覧（管理者/一般で出し分け）
     * GET /stamp_correction_request/list
     * route: requests.index
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        // ✅ 管理者：全ユーザー分（管理者用view）
        if (Auth::guard('admin')->check()) {

            $requests = AttendanceRequest::with(['attendance.user', 'user'])
                ->when(in_array($status, ['pending', 'approved', 'rejected'], true), function ($q) use ($status) {
                    $q->where('status', $status);
                })
                ->latest('id')
                ->paginate(20);

            // ページングに status を保持
            $requests->appends(['status' => $status]);

            return view('admin_request_list', compact('requests', 'status'));
        }

        // ✅ 一般ユーザー：本人分のみ（statusタブ対応）
        $requests = AttendanceRequest::with(['attendance', 'user'])
            ->where('user_id', $request->user()->id)
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest('id')
            ->paginate(20);

        // ページングに status を保持
        $requests->appends(['status' => $status]);

        return view('request_list', compact('requests', 'status'));
    }

    /**
     * 修正申請 作成（一般ユーザーのみ）
     * POST /stamp_correction_request
     * route: requests.store
     */
    public function store(StoreAttendanceRequest $request)
    {
        $data = $request->validated();

        // 本人の勤怠のみ
        $attendance = AttendanceList::with(['breaks', 'user'])
            ->where('id', $data['attendance_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // 同一勤怠に承認待ちがある場合はブロック
        $alreadyPending = AttendanceRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return redirect()
                ->route('attendance.detail', ['id' => $attendance->id])
                ->with('pending_notice', '既に承認待ちの修正申請が存在します。承認結果をお待ちください。');
        }

        // ===== before（保存用）=====
        $beforeBreaks = method_exists($attendance, 'breakPairsFormatted')
            ? $attendance->breakPairsFormatted()
            : $attendance->breaks->sortBy('start_at')->map(fn($b) => [
                'start' => $b->start_at?->format('H:i'),
                'end'   => $b->end_at?->format('H:i'),
            ])->values()->all();

        // ===== after（フォームから受け取った複数休憩を整形）=====
        $afterBreaksRaw = (array) ($request->input('after_breaks') ?? []);
        $afterBreaks = collect($afterBreaksRaw)
            ->map(function ($b) {
                $b = (array) $b;
                return [
                    'start' => $b['start'] ?? null,
                    'end'   => $b['end'] ?? null,
                ];
            })
            // 空行（startもendも空）は捨てる
            ->filter(fn($b) => filled($b['start']) || filled($b['end']))
            ->values()
            ->all();

        AttendanceRequest::create([
            'user_id'       => $request->user()->id,
            'attendance_id' => $attendance->id,
            'target_date'   => $attendance->work_date,
            'type'          => 'modify',

            // before
            'before_clock_in_at'  => optional($attendance->clock_in_at)->format('H:i'),
            'before_clock_out_at' => optional($attendance->clock_out_at)->format('H:i'),
            'before_breaks'       => $beforeBreaks,
            'before_note'         => $attendance->note,

            // after
            'after_clock_in_at'   => $data['after_clock_in_at']  ?? null,
            'after_clock_out_at'  => $data['after_clock_out_at'] ?? null,
            'after_breaks'        => $afterBreaks,
            'after_note'          => $data['after_note'] ?? null,

            // ✅ reason は使わない（DBにも無い）
            'status'     => 'pending',
            'applied_at' => now(),
        ]);

        return redirect()
            ->route('attendance.detail', ['id' => $attendance->id])
            ->with('pending_notice', '承認待ちのため修正はできません。');
    }

    /**
     * 修正申請 作成（管理者用）
     * POST /admin/stamp_correction_request/admin
     * route: admin.requests.store.admin
     */
    public function storeByAdmin(StoreAttendanceRequest $request)
    {
        $data = $request->validated();

        // 管理者は user_id で縛らず勤怠IDで取得（誰の勤怠でもOK）
        $attendance = AttendanceList::with(['breaks', 'user'])
            ->where('id', $data['attendance_id'])
            ->firstOrFail();

        // 同一勤怠に承認待ちがある場合はブロック
        $alreadyPending = AttendanceRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return redirect()
                ->route('admin.attendance.detail', ['id' => $attendance->id])
                ->with('pending_notice', '既に承認待ちの修正申請が存在します。承認結果をお待ちください。');
        }

        // ===== before（保存用）=====
        $beforeBreaks = method_exists($attendance, 'breakPairsFormatted')
            ? $attendance->breakPairsFormatted()
            : $attendance->breaks->sortBy('start_at')->map(fn($b) => [
                'start' => $b->start_at?->format('H:i'),
                'end'   => $b->end_at?->format('H:i'),
            ])->values()->all();

        // ===== after（フォームから受け取った複数休憩を整形）=====
        $afterBreaksRaw = (array) ($request->input('after_breaks') ?? []);
        $afterBreaks = collect($afterBreaksRaw)
            ->map(function ($b) {
                $b = (array) $b;
                return [
                    'start' => $b['start'] ?? null,
                    'end'   => $b['end'] ?? null,
                ];
            })
            ->filter(fn($b) => filled($b['start']) || filled($b['end']))
            ->values()
            ->all();

        AttendanceRequest::create([
            'user_id'       => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'target_date'   => $attendance->work_date,
            'type'          => 'modify',

            // before
            'before_clock_in_at'  => optional($attendance->clock_in_at)->format('H:i'),
            'before_clock_out_at' => optional($attendance->clock_out_at)->format('H:i'),
            'before_breaks'       => $beforeBreaks,
            'before_note'         => $attendance->note,

            // after
            'after_clock_in_at'   => $data['after_clock_in_at']  ?? null,
            'after_clock_out_at'  => $data['after_clock_out_at'] ?? null,
            'after_breaks'        => $afterBreaks,
            'after_note'          => $data['after_note'] ?? null,

            'status'     => 'pending',
            'applied_at' => now(),
        ]);

        return redirect()
            ->route('admin.attendance.detail', ['id' => $attendance->id])
            ->with('pending_notice', '承認待ちのため修正はできません。');
    }
}
