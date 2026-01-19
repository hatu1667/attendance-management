@extends('layouts.app')

@section('title', '勤怠詳細（承認）')

@push('styles')
@php
$css = 'css/attendance_detail.css';
$ver = file_exists(public_path($css)) ? ('?v='.filemtime(public_path($css))) : '';
@endphp
<link rel="stylesheet" href="{{ asset($css) }}{{ $ver }}">
@endpush

@section('content')
@php
/** @var \App\Models\AttendanceRequest|null $requestModel */
$requestModel = $requestModel ?? null;

/** @var \App\Models\AttendanceList|null $record */
$record = $record ?? ($requestModel?->attendance ?? null);

// 名前
$userName = $requestModel?->user?->name ?? $record?->user?->name ?? '';

// 日付
$date = $day ?? ($record?->work_date ?? now());
if (is_string($date)) { $date = \Carbon\Carbon::parse($date); }
if ($date instanceof \Carbon\Carbon === false) { $date = \Carbon\Carbon::parse($date); }

// 元データ（fallback）
$inBase = $record?->clock_in_at ? \Carbon\Carbon::parse($record->clock_in_at)->format('H:i') : '';
$outBase = $record?->clock_out_at ? \Carbon\Carbon::parse($record->clock_out_at)->format('H:i') : '';

// 申請の after_* を優先表示
$inVal = $requestModel?->after_clock_in_at ?? $inBase;
$outVal = $requestModel?->after_clock_out_at ?? $outBase;

// 休憩：申請 after_breaks を優先。無ければ元のbreaksから生成。
$breaksVal = $requestModel?->after_breaks;
if (!is_array($breaksVal)) {
$breaksVal = $record
? $record->breaks->sortBy('start_at')->map(fn($b) => [
'start' => $b->start_at?->format('H:i'),
'end' => $b->end_at?->format('H:i'),
])->values()->all()
: [];
}
$breaksVal = is_array($breaksVal) ? $breaksVal : [];

// 備考：申請 after_note → 無ければ元データ note
$noteVal = $requestModel?->after_note ?? ($record?->note ?? '');
@endphp

<main class="container" role="main">
    <h1 class="section-title">勤怠詳細</h1>

    <section class="detail-card">
        <div class="row">
            <div class="cell label">名前</div>
            <div class="cell value">{{ $userName }}</div>
        </div>

        <div class="row">
            <div class="cell label">日付</div>
            <div class="cell value value--date">
                <span class="date-year">{{ $date->format('Y') }}年</span>
                <span class="date-md">{{ $date->format('n月j日') }}</span>
            </div>
        </div>

        <div class="row">
            <div class="cell label">出勤・退勤</div>
            <div class="cell value value--range">
                <input class="time-input" type="text" value="{{ $inVal }}" readonly>
                <span class="tilde">〜</span>
                <input class="time-input" type="text" value="{{ $outVal }}" readonly>
            </div>
        </div>

        @php $breakCount = max(1, count($breaksVal)); @endphp
        @for ($i = 0; $i < $breakCount; $i++)
            @php
            $b=(array)($breaksVal[$i] ?? []);
            $s=$b['start'] ?? '' ;
            $e=$b['end'] ?? '' ;
            @endphp
            <div class="row">
            <div class="cell label">休憩{{ $i + 1 }}</div>
            <div class="cell value value--range">
                <input class="time-input" type="text" value="{{ $s }}" readonly>
                <span class="tilde">〜</span>
                <input class="time-input" type="text" value="{{ $e }}" readonly>
            </div>
            </div>
            @endfor

            <div class="row">
                <div class="cell label">備考</div>
                <div class="cell value">
                    <input class="note-input" type="text" value="{{ $noteVal }}" readonly>
                </div>
            </div>
    </section>

    {{-- 承認ボタン（pendingのときだけ） --}}
    @if (($requestModel->status ?? 'pending') === 'pending')
    <div class="actions" style="justify-content:flex-end">
        <form method="post" action="{{ route('admin.requests.approve', ['attendance_correct_request_id' => $requestModel->id]) }}">
            @csrf
            <button type="submit" class="btn-primary">承認</button>
        </form>
    </div>
    @else
    <div class="actions" style="justify-content:flex-end">
        <span class="btn-primary" style="background:#666; cursor:default;">承認済み</span>
    </div>
    @endif
</main>
@endsection