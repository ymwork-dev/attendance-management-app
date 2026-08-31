@extends('layouts.app')<!-- スタッフ用共通レイアウトを継承 -->

@section('title', '申請詳細')

@section('css')
    @vite(['resources/css/stamp_correction_request_detail.css'])
@endsection

@section('content')
<div class="attendanceDetailMain">
    <div class="attendanceDetailForm">
        <h1 class="attendanceDetailTitle">申請詳細</h1>

        <!-- 読み取り専用の履歴確認画面のため、送信フォームは無し -->
        <table class="attendanceTable">
            <tbody>
                <tr>
                    <th>名前</th>
                    <td>{{ $requestData->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <div class="dateDisplayGroup">
                            <span class="dateYearText">{{ \Carbon\Carbon::parse($requestData->attendanceRecord->date)->format('Y年') }}</span>
                            <span class="dateDayText">{{ \Carbon\Carbon::parse($requestData->attendanceRecord->date)->format('n月j日') }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="timeRangeGroup">
                            <input class="inputTimeField" type="time" value="{{ $requestData->requested_clock_in ? \Carbon\Carbon::parse($requestData->requested_clock_in)->format('H:i') : '' }}" readonly>
                            <span class="timeSeparator">〜</span>
                            <input class="inputTimeField" type="time" value="{{ $requestData->requested_clock_out ? \Carbon\Carbon::parse($requestData->requested_clock_out)->format('H:i') : '' }}" readonly>
                        </div>
                    </td>
                </tr>

                <!-- 申請時の休憩データを順番に表示 -->
                @if(!empty($requestData->requested_breaks))
                    @foreach(array_values($requestData->requested_breaks) as $index => $break)
                        <tr>
                            <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                            <td>
                                <div class="timeRangeGroup">
                                    <input class="inputTimeField" type="time" value="{{ isset($break['break_in']) ? \Carbon\Carbon::parse($break['break_in'])->format('H:i') : '' }}" readonly>
                                    <span class="timeSeparator">〜</span>
                                    <input class="inputTimeField" type="time" value="{{ isset($break['break_out']) ? \Carbon\Carbon::parse($break['break_out'])->format('H:i') : '' }}" readonly>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif

                <tr>
                    <th>備考</th>
                    <td>
                        <textarea class="textareaRemarksField" readonly>{{ $requestData->comment }}</textarea>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 申請の状態(承認待ち・承認済み・却下済み)を表示 -->
    <div class="approvalPendingOutside">
        <p class="approvalPendingMessage">
            @if ($requestData->status === 'pending')
                承認待ち
            @elseif ($requestData->status === 'approved')
                承認済み
            @else
                却下済み
            @endif
        </p>
    </div>
</div>
@endsection
