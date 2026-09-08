@extends('layouts.admin')

@section('title', '勤怠一覧')

@section('css')
@vite(['resources/css/admin_attendance_list.css'])
@endsection

@section('content')
<div class="attendance-list-container">
    <!-- 念月日の表示 -->
    <h2>{{ $date->format('Y年n月j日') }}の勤怠</h2>

    <!-- 日付変更エリア -->
    <nav class="date-selector">
        <a class="nav-btn" href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}">← 前日</a>
        <span class="current-date-display">
            <!-- 線画のカレンダーアイコン -->
            <svg class="cal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3.5" y="5" width="17" height="15" rx="2.5"/>
                <path d="M8 3v4M16 3v4M3.5 10h17"/>
            </svg>
            {{ $date->format('Y/m/d') }}
        </span>
        <a class="nav-btn" href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}">翌日 →</a>
    </nav>

    <!-- テーブルエリア -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
        <tbody>
                <!-- 勤怠一覧の1行分ずつ取り出す -->
                @foreach ($attendanceRecords as $record)
                    <tr>
                        <!-- ユーザー名を表示 -->
                        <td>{{ $record->user->name }}</td>

                        <!-- 出勤時刻をH:i形式で表示 -->
                        <td>{{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '' }}</td>

                        <!-- 退勤時刻をH:i形式で表示 -->
                        <td>{{ $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '' }}</td>

                        <!-- 休憩時間を表示 -->
                        <td>{{ $record->total_break_time }}</td>

                        <!-- 勤務時間を表示 -->
                        <td>{{ $record->total_time }}</td>

                        <!-- 詳細画面へのリンク -->
                        <td>
                            <a class="detail-link" href="{{ route('admin.attendance.detail', ['id' => $record->id]) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection