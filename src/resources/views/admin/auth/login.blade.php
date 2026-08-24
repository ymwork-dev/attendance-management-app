@extends('layouts.admin')

@section('title', '管理者ログイン')

@section('css')
@vite(['resources/css/admin_login.css'])
@endsection

@section('content')
<div class="login-page-only">
    <div class="login-container">
        <h2 class="login-title">管理者ログイン</h2>

        @if (session('login_failed') || $errors->has('login_failed'))
            <div class="error-message">
                {{ session('login_failed') ?? $errors->first('login_failed') }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" novalidate>
            <!--不正なPOST送信を防ぐ認証トークン-->
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password">
                @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button class="form-button" type="submit">管理者ログインする</button>
        </form>
    </div>
</div>
@endsection
