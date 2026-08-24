<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ブラウザのタブにタイトルを表示する -->
    <title>@yield('title', 'COACHTECH')</title>
    <!-- 対応CSSを読み込む Vite(高速なフロントエンド構築ツール) -->
    @vite(['resources/css/app.css'])
    <!-- 親が子を読み込む場所-->
    @yield('css')
</head>

<body>
    <!-- 共通の黒ヘッダーバー -->
    <header class="header">
        <!-- ヘッダーの内側 -->
        <div class="header-inner">
            <!-- COACHTECH ロゴを 画像で挿入 -->
            <div class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="COACHTECH">
            </div>
            <!-- ログインユーザー用のヘッダーに表示 -->
            @auth
            <nav class="nav">
                <!-- もし退勤済み（フラグが確実に真）だったら退勤後用のメニューを表示する -->
                @if(!empty($is_clocked_out) && $is_clocked_out === true)
                    <a href="/attendance/list">今月の出勤一覧</a>
                    <a href="{{ route('attendance_correction_request.index') }}">申請一覧</a>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
                <!-- 退勤前、勤務中、または他のレポート画面などの場合は通常のメニューを表示する -->
                @else
                    <a href="{{ route('attendance.index') }}">勤怠</a>
                    <a href="/attendance/list">勤怠一覧</a>
                    <a href="{{ route('attendance_correction_request.index') }}">申請</a>
                    <a href="{{ route('attendance.report') }}">レポート</a>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
                @endif
            </nav>
            @endauth
        </div>
    </header>

    <!-- ログインユーザー用のヘッダーに表示 -->
    @auth
    <!-- ログアウト用の隠しフォーム -->
    <form id="logout-form" action="/logout" method="POST" style="display: none;">
        <!-- cookie自動送信を悪用した攻撃を防ぐための@csrf(セキュリティトークン) -->
        @csrf
    </form>
    @endauth

    <!-- 親が子を読み込む場所 -->
    <main>
        @yield('content')
    </main>

</body>
</html>
