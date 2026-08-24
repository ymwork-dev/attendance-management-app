@extends('layouts.admin')

@section('css')
@vite(['resources/css/admin_staff_attendance.css'])
@endsection

@section('content')
<div id="attendance-list-container">
    <!-- スタッフ一覧からのスタッフ別一覧 -->
    <h2 class="attendance-title-wrapper">
        <span class="title-text">{{ $targetUser->name }}さんの勤怠</span>

        <!-- 修正完了メッセージ -->
        @if (session('success_message'))
            <div class="success-message-wrapper">
                <p class="successMessage">{{ session('success_message') }}</p>
            </div>
        @endif
    </h2>

    <div id="month-selector">
        <a href="{{ route('admin.attendance.staff', ['id' => $targetUser->id, 'month' => $prevMonth]) }}" class="nav-btn">&larr; 前月</a>
        <span class="current-month-display">
            <span class="calendar-icon">📅</span>{{ str_replace(['年', '月'], ['/', ''], $currentMonth) }}
        </span>
        <a href="{{ route('admin.attendance.staff', ['id' => $targetUser->id, 'month' => $nextMonth]) }}" class="nav-btn">翌月 &rarr;</a>
    </div>

    <div class="table-wrapper">
        <table>
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
                <!-- 年月日・曜日・勤怠データをを1日ずつ取得して一覧表示-->
                @foreach($daysInMonth as $day)
                    @php
                        $dateStr = $day->format('Y-m-d');
                        $record = $attendances->get($dateStr);
                        $wago = ['日', '月', '火', '水', '木', '金', '土'];
                        $dayOfWeek = $wago[$day->dayOfWeek];
                    @endphp
                    <tr>
                        <!-- 出勤・退勤・休憩・労働時間を表示-->
                        <td>{{ $day->format('m/d') }}({{ $dayOfWeek }})</td>
                        <td>{{ $record && $record->display_clock_in ? \Carbon\Carbon::parse($record->display_clock_in)->format('H:i') : '' }}</td>
                        <td>{{ $record && $record->display_clock_out ? \Carbon\Carbon::parse($record->display_clock_out)->format('H:i') : '' }}</td>
                        <td>{{ $record ? $record->display_break_time : '' }}</td>
                        <td>{{ $record ? $record->display_work_time : '' }}</td>
                        <td>
                            @if($record)
                                <a href="{{ route('admin.attendance.detail', ['id' => $record->id]) }}" class="detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="csv-button-panel">
        <a href="{{ route('admin.attendance.staff.csv', ['id' => $targetUser->id, 'month' => request('month')]) }}" class="csv-download-btn">CSV出力</a>
    </div>
</div>
@endsection
