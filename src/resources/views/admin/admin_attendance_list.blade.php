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
        <span class="current-date-display">📅 {{ $date->format('Y/m/d') }}</span>
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
                    @php
                        // 休憩時間の合計を0分でスタート
                        $totalBreakMinutes = 0;

                        // この勤怠データに紐づく休憩データを1件ずつ調べる
                        // 休憩開始・終了の両方が入っている時だけ計算する
                        foreach ($record->breaks as $breakLog) {
                            if ($breakLog->break_in && $breakLog->break_out) {
                                $breakIn = \Carbon\Carbon::parse($breakLog->break_in);
                                $breakOut = \Carbon\Carbon::parse($breakLog->break_out);

                                // 休憩時間の計算
                                $totalBreakMinutes += $breakIn->diffInMinutes($breakOut, true);
                            }
                        }

                        // 分を時間と分に計算する処理
                        $breakHours = floor($totalBreakMinutes / 60);
                        $breakMinutes = $totalBreakMinutes % 60;

                        // 時間形式 00:00 に変換
                        $formattedBreakTime = sprintf('%02d:%02d', $breakHours, $breakMinutes);

                        // 勤務時間を最初は空っぽにしておく
                        $formattedWorkTime = '';

                        // 出勤時刻・退勤時刻の両方が入っている時だけ計算する Carbonに変換
                        if ($record->clock_in && $record->clock_out) {
                            $clockIn = \Carbon\Carbon::parse($record->clock_in);
                            $clockOut = \Carbon\Carbon::parse($record->clock_out);

                            // 出勤～退勤までの全体時間を分で計算
                            $totalWorkMinutes = $clockIn->diffInMinutes($clockOut, true);

                            // 全体時間から休憩時間を引いて勤務時間をだす
                            $workMinutes = $totalWorkMinutes - $totalBreakMinutes;

                            // 分を時間と分に分ける処理
                            $workHours = floor($workMinutes / 60);
                            $workRemainMinutes = $workMinutes % 60;

                            // 時間形式 00:00 に変換
                            $formattedWorkTime = sprintf('%02d:%02d', $workHours, $workRemainMinutes);
                        }
                    @endphp


                    <tr>
                        <!-- ユーザー名を表示 -->
                        <td>{{ $record->user->name }}</td>

                        <!-- 出勤時刻をH:i形式で表示 -->
                        <td>{{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '' }}</td>

                        <!-- 退勤時刻をH:i形式で表示 -->
                        <td>{{ $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '' }}</td>

                        <!-- 休憩時間を表示 -->
                        <td>{{ $formattedBreakTime }}</td>

                        <!-- 勤務時間を表示 -->
                        <td>{{ $formattedWorkTime }}</td>

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