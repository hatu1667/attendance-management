@extends('layouts.app')

@section('title', '勤怠詳細')

@push('styles')
@php
$css = 'css/attendance_detail.css';
$ver = file_exists(public_path($css)) ? ('?v='.filemtime(public_path($css))) : '';
@endphp
<link rel="stylesheet" href="{{ asset($css) }}{{ $ver }}">
@endpush

@section('content')
@php
/** @var \App\Models\AttendanceList|null $record */
$record = $record ?? null;

/** @var \App\Models\AttendanceRequest|null $pendingRequest */
$pendingRequest = $pendingRequest ?? null;

$hasPendingRequest = $hasPendingRequest ?? false;

// 日付
$date = ($date_obj ?? $day ?? now());
if (is_string($date)) { $date = \Carbon\Carbon::parse($date); }

// 出退勤（元データ表示用）
$in = $record?->clock_in_at instanceof \Carbon\Carbon ? $record->clock_in_at->format('H:i')
: ($record?->clock_in_at ? \Carbon\Carbon::parse($record->clock_in_at)->format('H:i') : '');

$out = $record?->clock_out_at instanceof \Carbon\Carbon ? $record->clock_out_at->format('H:i')
: ($record?->clock_out_at ? \Carbon\Carbon::parse($record->clock_out_at)->format('H:i') : '');

// 休憩ペア（元データ）
$pairs = $pairs ?? ($record
? $record->breaks->sortBy('start_at')->map(fn($b) => [
'start' => $b->start_at?->format('H:i'),
'end' => $b->end_at?->format('H:i'),
])->values()->all()
: []);

// 名前
$userName = optional(optional($record)->user)->name ?? optional(auth()->user())->name ?? '';

// ★表示用：pendingがあれば申請の after_* を優先して見せる
$inVal = old('after_clock_in_at', $pendingRequest->after_clock_in_at ?? $in);
$outVal = old('after_clock_out_at', $pendingRequest->after_clock_out_at ?? $out);

// 休憩：pendingRequest->after_breaks があればそれを表示
$breaksVal = old('after_breaks', $pendingRequest->after_breaks ?? $pairs);
$breaksVal = is_array($breaksVal) ? $breaksVal : [];

// 備考：pendingがあれば after_note を表示
$noteVal = old('after_note', $pendingRequest->after_note ?? ($record?->note ?? ''));

// 入力不可（pending中なら true）
$ro = $hasPendingRequest ? 'readonly' : '';
@endphp

<main class="container" role="main">
    <h1 class="section-title">勤怠詳細</h1>

    <section class="detail-card">

        @if ($record)
        <form id="reqForm" method="post" action="{{ route('requests.store') }}" novalidate>

            @csrf
            <input type="hidden" name="attendance_id" value="{{ $record->id }}">

            {{-- 名前 --}}
            <div class="row">
                <div class="cell label">名前</div>
                <div class="cell value">{{ $userName }}</div>
            </div>

            {{-- 日付 --}}
            <div class="row">
                <div class="cell label">日付</div>
                <div class="cell value value--date">
                    <span class="date-year">{{ $date->format('Y') }}年</span>
                    <span class="date-md">{{ $date->format('n月j日') }}</span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="row">
                <div class="cell label">出勤・退勤</div>
                <div class="cell value value--range">
                    <input class="time-input"
                        name="after_clock_in_at"
                        placeholder="HH:MM"
                        value="{{ $inVal }}"
                        inputmode="numeric"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        {{ $ro }}>
                    <span class="tilde">〜</span>
                    <input class="time-input"
                        name="after_clock_out_at"
                        placeholder="HH:MM"
                        value="{{ $outVal }}"
                        inputmode="numeric"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        {{ $ro }}>
                </div>
            </div>

            {{-- ✅ 出勤・退勤エラー（入力欄の下に表示） --}}
            @error('after_clock_in_at')
            <p class="form-error">{{ $message }}</p>
            @enderror

            @error('after_clock_out_at')
            <p class="form-error">{{ $message }}</p>
            @enderror


            {{-- 休憩 --}}
            @php $breakCount = max(1, count($breaksVal)); @endphp

            @for ($i = 0; $i < $breakCount; $i++)
                @php
                $b=(array)($breaksVal[$i] ?? []);
                $s=old("after_breaks.$i.start", $b['start'] ?? '' );
                $e=old("after_breaks.$i.end", $b['end'] ?? '' );
                @endphp

                <div class="row">
                <div class="cell label">休憩{{ $i + 1 }}</div>
                <div class="cell value value--range">
                    <input class="time-input"
                        name="after_breaks[{{ $i }}][start]"
                        value="{{ $s }}"
                        placeholder="HH:MM"
                        inputmode="numeric"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        {{ $ro }}>
                    <span class="tilde">〜</span>
                    <input class="time-input"
                        name="after_breaks[{{ $i }}][end]"
                        value="{{ $e }}"
                        placeholder="HH:MM"
                        inputmode="numeric"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        {{ $ro }}>
                </div>
                </div>

                {{-- ✅ 休憩エラー（各行の下に表示） --}}
                @error("after_breaks.$i.start")
                <p class="form-error">{{ $message }}</p>
                @enderror

                @error("after_breaks.$i.end")
                <p class="form-error">{{ $message }}</p>
                @enderror
                @endfor


                {{-- 次の休憩（追加用）：pending中は出さない --}}
                @if (!$hasPendingRequest)
                <div class="row row--keep-border">
                    <div class="cell label">休憩{{ $breakCount + 1 }}</div>
                    <div class="cell value value--range">
                        <input class="time-input"
                            name="after_breaks[{{ $breakCount }}][start]"
                            placeholder="HH:MM"
                            inputmode="numeric"
                            pattern="^([01]\d|2[0-3]):[0-5]\d$">
                        <span class="tilde">〜</span>
                        <input class="time-input"
                            name="after_breaks[{{ $breakCount }}][end]"
                            placeholder="HH:MM"
                            inputmode="numeric"
                            pattern="^([01]\d|2[0-3]):[0-5]\d$">
                    </div>
                </div>

                {{-- ✅ 追加行のエラーも表示（必要なら） --}}
                @error("after_breaks.$breakCount.start")
                <p class="form-error">{{ $message }}</p>
                @enderror

                @error("after_breaks.$breakCount.end")
                <p class="form-error">{{ $message }}</p>
                @enderror
                @endif


                {{-- 備考 --}}
                <div class="row">
                    <div class="cell label">備考</div>
                    <div class="cell value">
                        <input class="note-input"
                            name="after_note"
                            type="text"
                            value="{{ $noteVal }}"
                            {{ $ro }}>
                    </div>
                </div>

                {{-- ✅ 備考エラー --}}
                @error('after_note')
                <p class="form-error">{{ $message }}</p>
                @enderror


                {{-- 修正ボタン（pending中は消す） --}}
                @if (!$hasPendingRequest)
                <div class="actions" style="justify-content:flex-end">
                    <button type="submit" class="btn-primary">修正</button>
                </div>
                @endif

        </form>
        @endif

        {{-- 承認待ちメッセージ（pending中 or 送信直後） --}}
        @if (session('pending_notice') || $hasPendingRequest)
        <p class="note-danger">
            {{ session('pending_notice') ?? '※承認待ちのため修正はできません。' }}
        </p>
        @endif

    </section>
</main>
@endsection