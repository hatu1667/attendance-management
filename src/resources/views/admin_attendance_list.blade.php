@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endpush

@section('content')
<main class="a-page">
    <div class="a-page__inner">

        <h1 class="a-title">
            <span class="a-title__bar"></span>
            {{ $date->format('Y年n月j日') }}の勤怠
        </h1>

        {{-- 日付ナビ --}}
        <div class="a-nav">
            <a class="a-nav__btn" href="{{ route('admin.attendance.list', ['d' => $date->copy()->subDay()->format('Y-m-d')]) }}">
                ← 前日
            </a>

            <form class="a-nav__form" method="get" action="{{ route('admin.attendance.list') }}">
                <label class="a-nav__label">
                    <span class="a-nav__icon" aria-hidden="true"></span>
                    <input
                        type="date"
                        name="d"
                        value="{{ $date->format('Y-m-d') }}"
                        class="a-nav__date"
                        onchange="this.form.submit()">
                </label>
            </form>

            <a class="a-nav__btn" href="{{ route('admin.attendance.list', ['d' => $date->copy()->addDay()->format('Y-m-d')]) }}">
                翌日 →
            </a>
        </div>

        {{-- テーブル --}}
        <div class="a-card">
            <table class="a-table">
                <thead>
                    <tr>
                        <th class="col-name">名前</th>
                        <th class="col-time">出勤</th>
                        <th class="col-time">退勤</th>
                        <th class="col-break">休憩</th>
                        <th class="col-total">合計</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $r)
                    <tr>
                        <td class="td-center">{{ $r['name'] }}</td>
                        <td class="td-center">{{ $r['clock_in'] ?? '' }}</td>
                        <td class="td-center">{{ $r['clock_out'] ?? '' }}</td>
                        <td class="td-center">{{ $r['break'] ?? '' }}</td>
                        <td class="td-center">{{ $r['total'] ?? '' }}</td>
                        <td class="td-center">
                            <a href="{{ route('admin.attendance.detail', $r['attendance_id']) }}">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="td-empty">データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</main>
@endsection