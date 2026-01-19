@extends('layouts.app')

@section('title', '申請一覧')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endpush

@section('content')
<main class="container" role="main">
    <h2 class="section-title"><span class="bar"></span>申請一覧</h2>

    {{-- 下線だけのタブ --}}
    @php $status = request('status', 'pending'); @endphp
    <div class="status-tabs tabs-plain">
        <a class="tab {{ $status==='pending' ? 'is-active' : '' }}"
            href="{{ route('requests.index', ['status' => 'pending']) }}">承認待ち</a>
        <a class="tab {{ $status==='approved' ? 'is-active' : '' }}"
            href="{{ route('requests.index', ['status' => 'approved']) }}">承認済み</a>
    </div>

    {{-- カード（枠付き）+ コンパクトテーブル --}}
    <section class="table-wrap table-compact">
        <table class="req-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th class="th-detail">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                <tr>
                    <td>
                        {{ ['pending'=>'承認待ち','approved'=>'承認済み','rejected'=>'却下'][$r->status] ?? $r->status }}
                    </td>
                    <td>{{ optional($r->user)->name ?? '—' }}</td>
                    <td>{{ optional($r->target_date)->format('Y/m/d') ?? '—' }}</td>

                    <td>{{ $r->after_note ?: '—' }}</td>

                    <td>{{ optional($r->applied_at)->format('Y/m/d H:i') ?? '—' }}</td>
                    <td class="td-detail">
                        <a class="detail-link" href="{{ route('attendance.detail', ['id' => $r->attendance_id]) }}">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty">申請はありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="pagination">{{ $requests->links() }}</div>
</main>
@endsection