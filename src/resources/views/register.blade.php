@extends('layouts.app')

@section('title', '会員登録')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endpush

@section('content')
<main class="container" role="main">
    <h1 class="page-title">会員登録</h1>

    <section class="card" aria-labelledby="register-title">
        <h2 id="register-title" class="sr-only">会員登録フォーム</h2>

        <form class="register-form" method="post" action="{{ route('register.store') }}">
            @csrf

            <div class="form-field">
                <label class="form-label" for="name">名前</label>
                <input class="form-input" id="name" name="name" type="text" value="{{ old('name') }}">
                @error('name')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}">
                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input" id="password" name="password" type="password">
                @error('password')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="password_confirmation">パスワード確認</label>
                <input class="form-input" id="password_confirmation" name="password_confirmation" type="password">
            </div>

            <button class="submit-btn" type="submit">登録する</button>

            <p class="login-link">
                <a href="{{ route('login.show') }}">ログインはこちら</a>
            </p>
        </form>
    </section>
</main>
@endsection