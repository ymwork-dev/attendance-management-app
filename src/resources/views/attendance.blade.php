@extends('layouts.app')<!-- 共通の親ヘッダーと app.css を読み込む -->

@section('title', '勤怠登録画面')<!-- ブラウザのタブにタイトルを表示する -->

<!-- 専用のCSSを読み込み Vite(高速なフロントエンド構築ツール) -->
@section('css')
    @vite(['resources/css/attendance.css'])
@endsection

<!-- 親ファイルのcontentスペースに流し込む -->
@section('content')
    <!-- 勤怠登録画面全体のレイアウトを整えるコンテナ -->
    <div class="container">

        <!-- ステータスタグの表示 -->
        <!-- もし勤務中でないなら勤務外と表示 -->
        @if (!$attendance)
            <div class="status-tag">勤務外</div>
        <!-- 退勤してない場合 -->
        @elseif ($attendance && !$attendance->clock_out)
            <!-- もし休憩中なら休憩中表示 -->
            @if ($is_breaking)
                <div class="status-tag">休憩中</div>
            <!-- そうでないなら出勤中表示 -->
            @else
                <div class="status-tag">出勤中</div>
            @endif
        <!-- 退勤しているなら退勤済表示 -->
        @else
            <div class="status-tag">退勤済</div>
        @endif

        <!-- コントローラーから渡された今日の日付を日付形式で取得する-->
        <div class="date">{{ $today }}</div>
        <!-- Carbonにより現在の時刻を時刻形式で取得して表示する-->
        <div class="time">{{ \Carbon\Carbon::now()->format('H:i') }}</div>

        <!-- ボタン表示エリア -->
        <div class="button-area">
            <!-- もし出勤してないなら -->
            @if (!$attendance)
                <!--出勤ルートへデータを送信する仕組み -->
                <form action="{{ route('attendance.clock-in') }}" method="POST">
                    <!-- cookie自動送信を悪用した攻撃を防ぐための@csrf(セキュリティトークン) -->
                    @csrf
                    <!-- ★classを btn-attendance btn-black に変更します -->
                    <button type="submit" class="btn-attendance btn-black">出勤</button>
                </form>

            <!-- 退勤してない場合 -->
            @elseif ($attendance && !$attendance->clock_out)
                <!-- もし休憩中なら -->
                @if ($is_breaking)
                    <!--休憩中ルートへデータを送信する仕組み -->
                    <form action="{{ route('attendance.break') }}" method="POST">
                        @csrf
                        <!-- 休憩戻ボタン表示し、クリック時に休憩中ルートへデータを送信する -->
                        <button type="submit" class="btn-attendance btn-white">休憩戻</button>
                    </form>
                <!-- 休憩中でない場合 -->
                @else
                    <!--退勤ルートへデータを送信する仕組み -->
                    <form action="{{ route('attendance.clock-out') }}" method="POST">
                        @csrf
                        <!-- 退勤ボタン表示し、クリック時に退勤ルートへデータを送信する -->
                        <button type="submit" class="btn-attendance btn-black">退勤</button>
                    </form>

                    <!--休憩ルートへデータを送信する仕組み -->
                    <form action="{{ route('attendance.break') }}" method="POST">
                        @csrf
                        <!-- 休憩入ボタン表示し、クリック時に休憩ルートへデータを送信する -->
                        <button type="submit" class="btn-attendance btn-white">休憩入</button>
                    </form>
                @endif

            <!--退勤している場合のメッセージ表示  -->
            @else
                <p class="attendance-end-message">お疲れ様でした。</p>
            @endif

        </div>
    </div>
@endsection
