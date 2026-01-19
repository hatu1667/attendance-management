@extends('layouts.app')

@section('title', 'スタッフ一覧')

@push('styles')
@php
$css = 'css/admin_staff_list.css';
$ver = file_exists(public_path($css)) ? ('?v='.filemtime(public_path($css))) : '';
@endphp
<link rel="stylesheet" href="{{ asset($css) }}{{ $ver }}">
@endpush

@section('content')
<main class="staff-page" role="main">
    <div class="staff-page__inner">
        <h1 class="page-title"><span class="page-title__bar"></span>スタッフ一覧</h1>

        <section class="staff-card">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th class="col-name">名前</th>
                        <th class="col-email">メールアドレス</th>
                        <th class="col-action">月次勤怠</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($staff as $s)
                    <tr>
                        <td class="td-name">{{ $s->name }}</td>
                        <td class="td-email">{{ $s->email }}</td>
                        <td class="td-action">
                            <a class="detail-link" href="{{ route('admin.staff.show', ['id' => $s->id]) }}">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="empty">スタッフがいません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- スクショに無いなら消してOK。残す場合も見た目は崩れないようにしてます --}}
        @if (method_exists($staff, 'links'))
        <div class="pagination-wrap">
            {{ $staff->links() }}
        </div>
        @endif
    </div>
</main>
@endsection