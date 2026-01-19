@extends('layouts.app')

@section('title', '管理者ログイン')

@push('styles')
@php
$css = 'css/login.css';
$ver = file_exists(public_path($css)) ? ('?v='.filemtime(public_path($css))) : '';
@endphp
<link rel="stylesheet" href="{{ asset($css) }}{{ $ver }}">
@endpush

@section('content')
<main class="container" role="main">
    <h1 class="page-title">管理者ログイン</h1>

    <section class="card" aria-labelledby="login-title">
        <h2 id="login-title" class="sr-only">ログインフォーム</h2>

        {{-- ブラウザ標準バリデーションを出さず、FormRequestの文言を出す --}}
        <form method="post" action="{{ route('admin.login.post') }}" novalidate>
            @csrf

            <div class="form-field">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input" id="email" name="email" type="email"
                    value="{{ old('email') }}" autocomplete="email">

                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input" id="password" name="password" type="password"
                    autocomplete="current-password">

                @error('password')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button class="submit-btn" type="submit">管理者ログインする</button>
        </form>
    </section>
</main>
@endsection