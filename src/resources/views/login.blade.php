@extends('layouts.app')

@section('title', 'ログイン')

@push('styles')
@php
$css = 'css/login.css';
$ver = file_exists(public_path($css)) ? ('?v='.filemtime(public_path($css))) : '';
@endphp
<link rel="stylesheet" href="{{ asset($css) }}{{ $ver }}">
@endpush

@section('content')
<main class="container" role="main">
    <h1 class="page-title">ログイン</h1>

    <section class="card" aria-labelledby="login-title">
        <h2 id="login-title" class="sr-only">ログインフォーム</h2>

        <form method="post" action="{{ route('login.post') }}" novalidate>
            @csrf

            <div class="form-field">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input"
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    value="{{ old('email') }}">
                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input"
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password">
                @error('password')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button class="submit-btn" type="submit">ログインする</button>

            <p class="signup-link">
                <a href="{{ route('register') }}">会員登録はこちら</a>
            </p>
        </form>
    </section>
</main>
@endsection