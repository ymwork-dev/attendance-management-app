@extends('layouts.app')

@section('css')
    @vite(['resources/css/attendance.report.css'])
@endsection

@section('content')
<div class="report-wrapper">
    <h1 class="report-title">マイ勤怠レポート</h1>
    <p class="report-subtitle">過去６ヶ月の勤怠データから集計しています。</p>

    <section class="report-section">
        <h2 class="section-title">基本サマリー</h2>
        <div class="card-container">
            <div class="report-card">
                <span class="card-label">総労働時間</span>
                <span class="card-value">{{ $summary['total_work']['h'] }}h {{ $summary['total_work']['m'] }}m</span>
            </div>
            <div class="report-card">
                <span class="card-label">総残業時間</span>
                <span class="card-value">{{ $summary['total_overtime']['h'] }}h {{ $summary['total_overtime']['m'] }}m</span>
            </div>
            <div class="report-card">
                <span class="card-label">平均労働時間 / 日</span>
                <span class="card-value">{{ $summary['average_work']['h'] }}h {{ $summary['average_work']['m'] }}m</span>
            </div>
        </div>
    </section>

    <section class="report-section">
        <h2 class="section-title">月次推移（過去６ヶ月）</h2>
        <div class="trend-table">
            <div class="table-header">
                <div class="col-month">月</div>
                <div class="col-work">労働時間</div>
                <div class="col-overtime">残業時間</div>
            </div>
            <!-- 月ごとの勤怠データを1件ずつ表示 -->
            @foreach($monthlyData as $month => $data)
            <div class="table-row">
                <!-- 月を表示 -->
                <div class="col-month">{{ $month }}</div>
                <!-- 労働時間を表示 -->
                <div class="col-work">{{ $data['work_hours'] }}h {{ $data['work_minutes'] }}m</div>
                <!-- 残業時間を表示 -->
                <div class="col-overtime">{{ $data['overtime_hours'] }}h {{ $data['overtime_minutes'] }}m</div>
            </div>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="section-title-with-subtitle">今月の異常検知</h2>
        <p class="section-subtitle">基準：始業 09:00 / 終業 18:00 / 長時間労働は 1 日 10 時間超</p>
        <div class="card-container">
            <div class="report-card">
                <span class="card-label">遅刻回数</span>
                <span class="card-value">{{ $anomaly['lateness'] }} 回</span>
            </div>
            <div class="report-card">
                <span class="card-label">早退回数</span>
                <span class="card-value">{{ $anomaly['early_leave'] }} 回</span>
            </div>
            <div class="report-card">
                <span class="card-label">長時間労働日数</span>
                <span class="card-value">{{ $anomaly['long_working'] }} 日</span>
            </div>
        </div>
    </section>

</div>
@endsection
