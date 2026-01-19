<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * 管理者：日別勤怠一覧
     * GET /admin/attendance/list?d=YYYY-MM-DD
     * view: admin_attendance_list（ここが $date 必須で落ちてたので必ず渡す）
     */
    public function index(Request $request)
    {
        $dateStr = $request->query('d');

        try {
            $date = $dateStr
                ? Carbon::createFromFormat('Y-m-d', $dateStr)->startOfDay()
                : today()->startOfDay();
        } catch (\Exception $e) {
            $date = today()->startOfDay();
        }

        $records = AttendanceList::query()
            ->select('attendance_lists.*')
            ->join('users', 'users.id', '=', 'attendance_lists.user_id')
            ->with(['user', 'breaks'])
            ->whereDate('work_date', $date->toDateString())
            ->orderBy('users.name')
            ->get();

        $rows = $records->map(function (AttendanceList $r) {
            $breakPairs = method_exists($r, 'breakPairsFormatted')
                ? $r->breakPairsFormatted()
                : $r->breaks->sortBy('start_at')->map(fn($b) => [
                    'start' => $b->start_at?->format('H:i'),
                    'end'   => $b->end_at?->format('H:i'),
                ])->values()->all();

            $breakStr = collect($breakPairs)
                ->map(fn($p) => trim(($p['start'] ?? '') . '–' . ($p['end'] ?? ''), '–'))
                ->filter()
                ->implode(' / ');
            if ($breakStr === '') $breakStr = '0:00';

            $totalStr = '0:00';
            if (method_exists($r, 'totalWorkedMinutes')) {
                $mins = $r->totalWorkedMinutes();
                if (!is_null($mins)) {
                    $totalStr = sprintf('%d:%02d', intdiv($mins, 60), $mins % 60);
                }
            }

            return [
                'attendance_id' => $r->id,
                'user_id'       => $r->user_id,
                'name'          => $r->user?->name ?? '(不明)',
                'clock_in'      => $r->clock_in_at?->format('H:i'),
                'clock_out'     => $r->clock_out_at?->format('H:i'),
                'break'         => $breakStr,
                'total'         => $totalStr,
            ];
        });

        // ✅ admin_attendance_list.blade.php が $date を使うので必ず渡す
        return view('admin_attendance_list', [
            'date' => $date,
            'rows' => $rows,
        ]);
    }

    /**
     * 管理者：スタッフ別 月別勤怠一覧
     * GET /admin/attendance/staff/{id}?ym=YYYY-MM
     * view名は任せると言ってくれたので admin_attendance_staff_list にしてます
     */

    public function staff(Request $request, int $id)
    {
        $staff = User::findOrFail($id);

        // ym=YYYY-MM（なければ今月）
        $ym = $request->query('ym', now()->format('Y-m'));
        try {
            $start = Carbon::parse($ym . '-01')->startOfMonth();
        } catch (\Exception $e) {
            $start = now()->startOfMonth();
            $ym = $start->format('Y-m');
        }
        $end = (clone $start)->endOfMonth();

        $records = AttendanceList::with(['breaks'])
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->work_date)->format('Y-m-d'));

        $days = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $key = $d->format('Y-m-d');
            $rec = $records[$key] ?? null;

            $cin  = $rec?->clock_in_at  ? Carbon::parse($rec->clock_in_at)->format('H:i') : null;
            $cout = $rec?->clock_out_at ? Carbon::parse($rec->clock_out_at)->format('H:i') : null;

            $breakPairs = $rec ? $rec->breakPairsFormatted() : [];
            $breakStr = collect($breakPairs)
                ->map(function ($p) {
                    $s = $p['start'] ?? '';
                    $e = $p['end'] ?? '';
                    return trim($s . '–' . $e, '–');
                })
                ->filter()
                ->implode(' / ');
            if ($breakStr === '') $breakStr = '0:00';

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

        $prevYm = $start->copy()->subMonth()->format('Y-m');
        $nextYm = $start->copy()->addMonth()->format('Y-m');
        $ymDisplay = $start->format('Y年n月');

        return view('admin_attendance_staff_list', compact(
            'staff',
            'days',
            'ym',
            'prevYm',
            'nextYm',
            'ymDisplay'
        ));
    }

    public function exportStaffCsv(Request $request, int $id): StreamedResponse
    {
        $staff = User::findOrFail($id);

        $ym = $request->query('ym', now()->format('Y-m'));
        try {
            $start = Carbon::parse($ym . '-01')->startOfMonth();
        } catch (\Exception $e) {
            $start = now()->startOfMonth();
            $ym = $start->format('Y-m');
        }
        $end = (clone $start)->endOfMonth();

        $records = AttendanceList::with(['breaks'])
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->work_date)->format('Y-m-d'));

        $filename = 'attendance_' . $staff->id . '_' . $ym . '.csv';

        return response()->streamDownload(function () use ($start, $end, $records, $staff) {
            $out = fopen('php://output', 'w');

            // Excel文字化け対策（UTF-8 BOM）
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['スタッフ', '日付', '出勤', '退勤', '休憩', '合計']);

            for ($d = $start->copy(); $d <= $end; $d->addDay()) {
                $key = $d->format('Y-m-d');
                $rec = $records[$key] ?? null;

                $cin  = $rec?->clock_in_at  ? Carbon::parse($rec->clock_in_at)->format('H:i') : '';
                $cout = $rec?->clock_out_at ? Carbon::parse($rec->clock_out_at)->format('H:i') : '';

                $breakPairs = $rec ? $rec->breakPairsFormatted() : [];
                $breakStr = collect($breakPairs)
                    ->map(fn($p) => trim(($p['start'] ?? '') . '–' . ($p['end'] ?? ''), '–'))
                    ->filter()
                    ->implode(' / ');
                if ($breakStr === '') $breakStr = '0:00';

                $totalStr = '0:00';
                if ($rec && ($mins = $rec->totalWorkedMinutes()) !== null) {
                    $totalStr = sprintf('%d:%02d', intdiv($mins, 60), $mins % 60);
                }

                fputcsv($out, [
                    $staff->name,
                    $d->format('Y-m-d'),
                    $cin,
                    $cout,
                    $breakStr,
                    $totalStr,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


    public function detail(int $id)
    {
        $record = AttendanceList::with(['user', 'breaks'])->findOrFail($id);

        $date = $record->work_date instanceof Carbon
            ? $record->work_date
            : Carbon::parse($record->work_date);

        return view('admin_attendance_detail', compact('record', 'date'));
    }

    public function edit(int $id)
    {
        $record = AttendanceList::with(['user', 'breaks'])->findOrFail($id);

        $date = $record->work_date instanceof Carbon
            ? $record->work_date
            : Carbon::parse($record->work_date);

        return view('admin_attendance_edit', compact('record', 'date'));
    }
}
