@extends('layouts.app')

@section('title', 'メール認証誘導画面')

@section('css')
    @vite(['resources/css/verify-email.css'])
@endsection

@section('content')
    <!-- メール認証画面メインエリア -->
    <div class="container">
        <!-- 認証を促すメッセージボタン -->
        <div class="verify-box">
            <!-- 見本通りのメッセージを表示 -->
            <div class="message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </div>
            <!-- 認証はこちらからボタン -->
            <a href="/email/go-to-mailpit" class="btn-verify" target="_blank">
                認証はこちらから
            </a>

            <!-- もし再送が成功したらお知らせを表示 -->
            @if (session('status') == 'verification-link-sent')
                <div class="alert-success">
                    新しい認証メールを再送信しました！
                </div>
            @endif

            <!-- 認証メールを再送するリンク（Laravel標準機能） -->
            <!-- cookie自動送信を悪用した攻撃を防ぐための@csrf(セキュリティトークン) -->
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="resend-link">
                    認証メールを再送する
                </button>
            </form>

        </div>
    </div>
@endsection
