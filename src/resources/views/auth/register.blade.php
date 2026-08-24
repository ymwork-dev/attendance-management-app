@extends('layouts.app') 

@section('title', '会員登録（一般ユーザー）')

@section('css')
    @vite(['resources/css/register.css'])
@endsection

@section('content')
<!--　会員登録メインエリア  -->
    <div class="register-box">
        <h1>会員登録</h1>

        <form action="/register" method="POST">
            @csrf

            <!-- お名前入力欄 -->
            <div class="form-group">
                <label>お名前</label>
                <!-- 認証失敗時も入力内容を保持 -->
                <input type="text" name="name" value="{{ old('name') }}">

                <!-- 入力欄下　エラーメッセージの表示 -->
                @error('name')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- メールアドレス入力欄 -->
            <div class="form-group">
                <label>メールアドレス</label>
                <input type="text" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- パスワード入力欄 -->
            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password">
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- 確認用パスワード入力欄 -->
            <div class="form-group">
                <label>確認用パスワード</label>
                <input type="password" name="password_confirmation">
            </div>

            <!-- 黒ボタン-->
            <button type="submit" class="btn">登録</button>
        </form>

        <!-- ログイン画面へ遷移リンク -->
        <p><a href="/login" class="login-link">ログインはこちら</a></p>
    </div>
@endsection
