@extends('layouts.app')

@section('title', '勤怠一覧画面')

@section('css')
    @vite(['resources/css/attendance_list.css'])
@endsection

@section('content')
<!-- 勤怠一覧画面全体のレイアウトを整えるコンテナ -->
<div id="attendance-list-container">

    <h1>勤怠一覧</h1>

    <!-- 指定月とその前月・翌月の選択欄 -->
    <div id="month-selector">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="nav-btn">&larr; 前月</a>
        <span class="current-month-display">
            <span>📅</span> {{ str_replace(['年', '月'], ['/', ''], $currentMonth) }}
        </span>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="nav-btn">翌月 &rarr;</a>
    </div>


    <!-- 勤怠データーを表示する全体の囲み -->
    <div class="table-wrapper">
        <table>
            <!--表の項目名  -->
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                <!-- コントローラーで用意した１日から月末を1日ずつ持ってきてループ処理をする -->
                <!-- データーを年月日形式にして変数(箱)にしまう -->
                <!--1日ずつ打刻データがあるか探し変数(箱)にしまう  -->
                <!-- 日本語の曜日を用意して変数(箱)にしまう -->
                <!-- 日付に合った正しい曜日を用意して変数(箱)にしまう -->
                @foreach($daysInMonth as $day)
                    @php
                        $dateStr = $day->format('Y-m-d');
                        $record = $attendances->get($dateStr);
                        $wago = ['日', '月', '火', '水', '木', '金', '土'];
                        $dayOfWeek = $wago[$day->dayOfWeek];
                    @endphp
                    <tr>
                        <!-- 月/日と(曜日)、出勤時刻、退勤時刻、休憩時間、勤務合計時間 を表示 -->
                        <td>{{ $day->format('m/d') }}({{ $dayOfWeek }})</td>
                        <td>{{ $record && $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '' }}</td>
                        <td>{{ $record && $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '' }}</td>
                        <td>{{ $record ? $record->display_break_time : '' }}</td>
                        <td>{{ $record ? $record->display_work_time : '' }}</td>
                        <td>
                            <!-- もし詳細リンクをクリックしたら、勤怠詳細画面を表示する -->
                            @if($record)
                                <a href="{{ route('attendance.detail', ['id' => $record->id]) }}" class="detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
