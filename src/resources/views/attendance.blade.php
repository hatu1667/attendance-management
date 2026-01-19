@extends('layouts.app')

@section('title', '勤怠')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endpush

@section('content')
<main class="container" role="main">

    @if(session('message'))
    <p style="text-align:center; color:#374151; margin:12px 0 0;">
        {{ session('message') }}
    </p>
    @endif

    <section class="attendance-card">
        @php
        /** @var \App\Models\AttendanceList $record */
        // 出勤中＝出勤はあるが退勤がまだ
        $working = !empty($record->clock_in_at) && empty($record->clock_out_at);

        // 未終了の休憩があるか（attendance_breaks ベース）
        // ※ コントローラで $record->loadMissing('breaks') 済み想定
        $hasOpenBreak = ($record->breaks ?? collect())->contains(fn($b) => is_null($b->end_at));

        // ボタン出し分け
        $canBreakStart = $working && !$hasOpenBreak;
        $canBreakEnd = $working && $hasOpenBreak;
        @endphp

        <h2 class="status-badge">{{ $working ? '勤務中' : '勤務外' }}</h2>

        <p class="date-text">{{ $now->locale('ja')->isoFormat('YYYY年M月D日(ddd)') }}</p>
        <div class="clock" id="live-time">{{ $now->format('H:i') }}</div>

        {{-- ボタン群 --}}
        @if (empty($record->clock_in_at))
        {{-- 出勤前：出勤のみ --}}
        <form method="post" action="{{ route('attendance.clock_in') }}" class="action">
            @csrf
            <button type="submit" class="btn-primary">出勤</button>
        </form>

        @elseif ($working)
        {{-- 勤務中：退勤 + 休憩入/戻 --}}
        <div class="action action--row" style="margin-top:20px; gap:12px;">
            <form method="post" action="{{ route('attendance.clock_out') }}">
                @csrf
                <button type="submit" class="btn-primary">退勤</button>
            </form>

            @if ($canBreakStart)
            <form method="post" action="{{ route('attendance.break_start') }}">
                @csrf
                <button type="submit" class="btn-secondary">休憩入</button>
            </form>
            @endif

            @if ($canBreakEnd)
            <form method="post" action="{{ route('attendance.break_end') }}">
                @csrf
                <button type="submit" class="btn-secondary">休憩戻</button>
            </form>
            @endif
        </div>

        @else
        {{-- 退勤後 --}}
        <p style="color:#6b7280; margin-top:16px;">お疲れ様でした。</p>
        @endif
    </section>
</main>

<script>
    (function() {
        const el = document.getElementById('live-time');
        const pad = (n) => n.toString().padStart(2, '0');

        function tick() {
            const d = new Date();
            el.textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endsection