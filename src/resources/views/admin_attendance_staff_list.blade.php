@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}?v={{ filemtime(public_path('css/attendance_list.css')) }}">
@endpush

@section('content')
<main class="container" role="main">

    {{-- タイトルだけユーザー名に --}}
    <h1 class="section-title">{{ $staff->name }}さんの勤怠</h1>

    {{-- 月ナビゲーション（一般ユーザーと同じ見た目） --}}
    <div class="month-nav" role="navigation" aria-label="月切り替え">
        <a class="nav-btn" href="{{ route('admin.attendance.staff', ['id' => $staff->id, 'ym' => $prevYm]) }}">← 前月</a>
        <div class="month-chip">{{ $ymDisplay }}</div>
        <a class="nav-btn" href="{{ route('admin.attendance.staff', ['id' => $staff->id, 'ym' => $nextYm]) }}">翌月 →</a>
    </div>

    {{-- テーブル（一般ユーザーと同じ） --}}
    <div class="table-wrap">
        <table class="att-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th class="th-detail">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($days as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td>{{ $day['clock_in']  ?? '-' }}</td>
                    <td>{{ $day['clock_out'] ?? '-' }}</td>
                    <td>{{ $day['break']     ?? '0:00' }}</td>
                    <td>{{ $day['total']     ?? '0:00' }}</td>
                    <td class="td-detail">
                        @if(!empty($day['id']))
                        <a href="{{ route('admin.attendance.detail', ['id' => $day['id']]) }}" class="detail-link">詳細</a>
                        @else
                        <span class="detail-link disabled">詳細</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="csv-actions">
        <a class="csv-btn"
            href="{{ route('admin.attendance.staff.export', ['id' => $staff->id, 'ym' => $ym]) }}">
            CSV出力
        </a>
    </div>

</main>
@endsection