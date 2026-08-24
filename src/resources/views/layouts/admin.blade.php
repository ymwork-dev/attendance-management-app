<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>

    @yield('title') - coachtech</title>
    @vite(['resources/js/app.js', 'resources/css/admin_common.css'])
    @yield('css')
    <link href="https://googleapis.com" rel="stylesheet">

</head>
<body>
    <header class="header">
        <h1 class="header__logo">
            <img src="{{ asset('img/logo.png') }}" alt="COACHTECH">
        </h1>

        <!-- 管理者としてログインしている場合のみヘッダーナビを表示 -->
        @if(Auth::check())
            <nav class="header__nav">
                <ul class="header__nav-list">
                    <li class="header__nav-item">
                        <a class="header__nav-link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <a class="header__nav-link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <a class="header__nav-link" href="{{ route('attendance_correction_request.index') }}">申請一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <!-- 安全にログアウトを実行するためのフォーム -->
                        <form action="{{ route('admin.logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button class="header__nav-link" type="submit" style="background: none; border: none; font-family: inherit;">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        @endif
    </header>

    <!-- 子のcontentセクションを表示 -->
    <main class="{{ Auth::check() ? 'main-content' : '' }}">
        @yield('content')
    </main>
</body>
</html>
