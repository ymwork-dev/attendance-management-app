@extends('layouts.app') <!-- 共通の親ヘッダーと app.css を読み込む -->

<!-- ブラウザのタブにタイトルを表示する -->
@section('title', 'ログイン（一般ユーザー）')

<!-- 専用のCSSを読み込み Vite(高速なフロントエンド構築ツール) -->
@section('css')
    @vite(['resources/css/login.css'])
@endsection

<!-- 親ファイルのcontentスペースに流し込む -->
@section('content')
    <!--ログイン画面メインエリア -->
    <div class="login-box">
        <h1>ログイン</h1>

        <!-- 画面上部 もし未登録ユーザーならエラーメッセージを表示 -->
        @if ($errors->any())
            <div class="error-message">
                @if ($errors->has('email') && str_contains($errors->first('email'), '登録されていません'))
                    {{ $errors->first('email') }}
                @endif
            </div>
        @endif

        <!-- laravel標準のFortifyパッケージのlogin機能にデータを送る -->
        <!-- cookie自動送信を悪用した攻撃を防ぐための@csrf(セキュリティトークン) -->
        <form action="/login" method="POST">
            @csrf

            <!-- メールアドレス入力欄 -->
            <div class="form-group">
                <label>メールアドレス</label>
                <!-- old('email')は認証失敗時も入力内容を保持 -->
                <input type="text" name="email" value="{{ old('email') }}">

                <!-- 入力欄下 もし未登録以外の不備ならエラーメッセージ表示 -->
                @error('email')
                    @if (!str_contains($message, '登録されていません'))
                        <div class="error-message">{{ $message }}</div>
                    @endif
                @enderror
            </div>

            <!-- パスワード入力欄 -->
            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password">
                <!-- もしパスワード入力に不備があったらエラーメッセージを表示 -->
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- 黒ボタン -->
            <button type="submit" class="btn">ログイン</button>
        </form>

        <!-- 会員登録画面へ遷移リンク -->
        <p><a href="/register" class="login-link">会員登録はこちら</a></p>
    </div>
@endsection
