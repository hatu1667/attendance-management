<?php

namespace App\Http\Controllers;

use App\Models\AttendanceList;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AttendanceRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $today = today();

        $record = AttendanceList::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            []
        );

        // ==== 状態フラグ ====
        $onBreak1 = $record->break_start && !$record->break_end;
        $onBreak2 = $record->break2_start && !$record->break2_end;

        $canBreakStart =
            $record->clock_in_at &&                 // 出勤していて
            !$record->clock_out_at &&               // 退勤前で
            !$onBreak1 && !$onBreak2 &&             // いま休憩中ではなく
            (                                       // 休憩1未開始 or 休憩1は完了して休憩2未開始
                !$record->break_start
                || ($record->break_start && $record->break_end && !$record->break2_start)
            );

        $canBreakEnd = $onBreak1 || $onBreak2;

        $record->loadMissing('breaks', 'user');

        $now = now();
        return view('attendance', compact('now', 'record', 'canBreakStart', 'canBreakEnd', 'onBreak1', 'onBreak2'));
    }

    public function clockIn(Request $request)
    {
        $r = AttendanceList::firstOrCreate(
            ['user_id' => $request->user()->id, 'work_date' => today()]
        );
        if (!$r->clock_in_at) {
            $r->clock_in_at = now();
            $r->save();
        }
        return redirect()->route('attendance.index');
    }

    public function clockOut(Request $request)
    {
        $r = AttendanceList::where('user_id', $request->user()->id)
            ->whereDate('work_date', today())->firstOrFail();

        if (!$r->clock_out_at) {
            $r->clock_out_at = now();
            $r->save();
        }
        return redirect()->route('attendance.index');
    }

    // 休憩入：未終了の休憩がある場合は何もしない、無ければ1本開始
    public function breakStart(Request $request)
    {
        $rec = AttendanceList::where('user_id', $request->user()->id)
            ->whereDate('work_date', today())->firstOrFail();

        $open = $rec->breaks()->whereNull('end_at')->first();
        if (!$open) {
            $rec->breaks()->create(['user_id'   => $rec->user_id,'start_at' => now()]);
        }
        return back();
    }

    // 休憩戻：未終了の最新休憩を終了
    public function breakEnd(Request $request)
    {
        $rec = AttendanceList::where('user_id', $request->user()->id)
            ->whereDate('work_date', today())->firstOrFail();

        $open = $rec->breaks()->whereNull('end_at')->latest('start_at')->first();
        if ($open) {
            $open->end_at = now();
            $open->save();
            return back();
        }
        return back();
    }




    public function list(Request $request)
    {
        $user = $request->user();
        $ym   = $request->query('ym', now()->format('Y-m'));

        $start     = \Carbon\Carbon::parse($ym . '-01')->startOfMonth();
        $end       = (clone $start)->endOfMonth();
        $prevYm    = $start->copy()->subMonth()->format('Y-m');
        $nextYm    = $start->copy()->addMonth()->format('Y-m');
        $ymDisplay = $start->format('Y年n月');

        $records = \App\Models\AttendanceList::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->work_date)->format('Y-m-d'));

        $days = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $key = $d->format('Y-m-d');
            /** @var \App\Models\AttendanceList|null $rec */
            $rec = $records[$key] ?? null;

            // 出退勤
            $cin  = $rec?->clock_in_at  ? \Carbon\Carbon::parse($rec->clock_in_at)->format('H:i') : null;
            $cout = $rec?->clock_out_at ? \Carbon\Carbon::parse($rec->clock_out_at)->format('H:i') : null;

            // 休憩：ペアを "HH:MM–HH:MM" で連結
            $breakPairs = $rec ? $rec->breakPairsFormatted() : [];
            $breakStr = collect($breakPairs)
                ->map(function ($p) {
                    $s = $p['start'] ?? '';
                    $e = $p['end'] ?? '';
                    return trim($s . '–' . $e, '–');
                })
                ->filter()              // 空は除外
                ->implode(' / ');
            if ($breakStr === '') $breakStr = '0:00';

            // 合計
            $totalStr = '0:00';
            if ($rec && ($mins = $rec->totalWorkedMinutes()) !== null) {
                $totalStr = sprintf('%d:%02d', intdiv($mins, 60), $mins % 60);
            }

            $days[] = [
                'id'        => $rec?->id,
                'date'      => $key,
                'label'     => $d->format('m/d'),
                'clock_in'  => $cin,
                'clock_out' => $cout,
                'break'     => $breakStr,
                'total'     => $totalStr,
            ];
        }

        return view('attendance_list', compact(
            'start',
            'end',
            'prevYm',
            'nextYm',
            'ymDisplay',
            'days'
        ));
    }


    public function show(Request $request, string $date)
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Exception $e) {
            abort(404);
        }

        $record = AttendanceList::with(['breaks', 'user'])
            ->where('user_id', $request->user()->id)
            ->whereDate('work_date', $day)
            ->first();

        $pairs = $record ? $record->breakPairsFormatted() : [];

        return view('attendance_detail', [
            'day'    => $day,
            'record' => $record,
            'pairs'  => $pairs,
            'clockIn' => $record?->clock_in_at?->format('H:i'),
            'clockOut' => $record?->clock_out_at?->format('H:i'),
            'total'  => $record ? (function ($mins) {
                return $mins === null ? '0:00' : sprintf('%d:%02d', intdiv($mins, 60), $mins % 60);
            })($record->totalWorkedMinutes()) : '0:00',
        ]);
    }

    public function detail(Request $request, int $id)
    {
        $rec = \App\Models\AttendanceList::with(['breaks', 'user'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $day   = \Carbon\Carbon::parse($rec->work_date)->startOfDay();
        $pairs = $rec->breakPairsFormatted();
        $in    = $rec->clock_in_at?->format('H:i');
        $out   = $rec->clock_out_at?->format('H:i');

        // ✅ pending申請（この勤怠に対して、本人の pending があれば取得）
        $pendingRequest = AttendanceRequest::where('attendance_id', $rec->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $hasPendingRequest = !is_null($pendingRequest);

        // 合計（任意）
        $totalStr = '0:00';
        if (($mins = $rec->totalWorkedMinutes()) !== null) {
            $totalStr = sprintf('%d:%02d', intdiv($mins, 60), $mins % 60);
        }

        return view('attendance_detail', [
            'day'              => $day,
            'record'           => $rec,
            'pairs'            => $pairs,
            'clockIn'          => $in,
            'clockOut'         => $out,
            'total'            => $totalStr,
            'hasPendingRequest' => $hasPendingRequest,
            'pendingRequest'   => $pendingRequest,
        ]);
    }
}