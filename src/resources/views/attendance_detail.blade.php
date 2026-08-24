@extends('layouts.app')<!-- ヘッダーの親ファイルとCSS継承 -->

<!-- 専用のCSSを読み込み Vite(高速なフロントエンド構築ツール) -->
@section('css')
    @vite(['resources/css/attendance_detail.css'])
@endsection

<!-- 親ファイルのcontentスペースに流し込む -->
@section('content')
    <div class="attendanceDetailMain">
        <div class="attendanceDetailForm">

            <h1 class="attendanceDetailTitle">勤怠詳細</h1>
            <main>
                <!-- ユーザーの勤怠情報を修正するための仕組み -->
                <form action="{{ route('attendance.update', $record->id) }}" method="POST" autocomplete="off">
                    <!-- cookie自動送信を悪用した攻撃を防ぐための@csrf(セキュリティトークン) -->
                    @csrf
                    <!-- HTMLではPOSTだがLaravelの更新はPATCHのため -->
                    @method('PATCH')

                    <!-- 勤怠詳細テーブル -->
                    <table class="attendanceTable">
                        <tbody>
                            <tr>
                                <th>名前</th>
                                <td>{{ $record->user->name }}</td>
                            </tr>
                            <tr>
                                <th>日付</th>
                                <td>
                                    <div class="dateDisplayGroup">
                                        <!-- 勤怠日の日付から年を取得して表示 -->
                                        <span class="dateYearText">{{ \Carbon\Carbon::parse($record->date)->format('Y年') }}</span>
                                        <!-- 勤怠日の日付から月日を取得して表示 -->
                                        <span class="dateDayText">{{ \Carbon\Carbon::parse($record->date)->format('n月j日') }}</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>出勤・退勤</th>
                                <td>
                                    <div class="timeRangeGroup">
                                        <!-- 出退勤時刻の修正申請入力欄 承認待ち申請詳細は修正不可-->
                                        <input type="time" name="clock_in" class="inputTimeField" value="{{ old('clock_in', $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '') }}" {{ optional($record->stampCorrectionRequest)->status === 'pending' ? 'readonly' : '' }}>
                                        <span class="timeSeparator">〜</span>
                                        <input type="time" name="clock_out" class="inputTimeField" value="{{ old('clock_out', $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '') }}" {{ optional($record->stampCorrectionRequest)->status === 'pending' ? 'readonly' : '' }}>
                                    </div>
                                    @if ($errors->has('clock_in') || $errors->has('clock_out'))
                                        <p class="inputErrorMessage">
                                            {{ $errors->first('clock_in') ?: $errors->first('clock_out') }}
                                        </p>
                                    @endif
                                </td>
                            </tr>

                            <!-- 登録済みの休憩時間を表示・編集 -->
                            @foreach($record->breaks as $index => $break)
                                <tr>
                                    <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                                    <td>
                                        <div class="timeRangeGroup">
                                            <!-- 休憩データ更新時に対象レコードを識別するため、休憩IDを送信 -->
                                            <input type="hidden" name="breaks[{{ $break->id }}][id]" value="{{ $break->id }}">

                                            <!-- 既存の休憩開始時刻を表示し、バリデーションエラー時は入力値を保持 -->
                                            <input
                                                type="time"
                                                name="breaks[{{ $break->id }}][break_in]"
                                                class="inputTimeField"
                                                value="{{ old('breaks.' . $break->id . '.break_in', $break->break_in ? \Carbon\Carbon::parse($break->break_in)->format('H:i') : '') }}"
                                                {{ optional($record->stampCorrectionRequest)->status === 'pending' ? 'readonly' : '' }}
                                            >

                                            <span class="timeSeparator">〜</span>

                                            <!-- 既存の休憩終了時刻を表示し、バリデーションエラー時は入力値を保持 -->
                                            <input
                                                type="time"
                                                name="breaks[{{ $break->id }}][break_out]"
                                                class="inputTimeField"
                                                value="{{ old('breaks.' . $break->id . '.break_out', $break->break_out ? \Carbon\Carbon::parse($break->break_out)->format('H:i') : '') }}"
                                                {{ optional($record->stampCorrectionRequest)->status === 'pending' ? 'readonly' : '' }}
                                            >
                                        </div>

                                        <!-- 休憩開始時刻のエラーメッセージ -->
                                        @error('breaks.' . $break->id . '.break_in')
                                            <p class="inputErrorMessage">{{ $message }}</p>
                                        @enderror

                                        <!-- 休憩終了時刻のエラーメッセージ -->
                                        @error('breaks.' . $break->id . '.break_out')
                                            <p class="inputErrorMessage">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach

                            <!-- 承認待ちでない場合は新しい休憩時間を追加可能 -->
                            @if(!$isPending)
                                <tr>
                                    <th>休憩{{ count($record->breaks) === 0 ? '' : count($record->breaks) + 1 }}</th>
                                    <td>

                                        <div class="timeRangeGroup">
                                            <input type="time" name="new_break_in" class="inputTimeField" value="{{ old('new_break_in') }}">
                                            <span class="timeSeparator">〜</span>
                                            <input type="time" name="new_break_out" class="inputTimeField" value="{{ old('new_break_out') }}">
                                        </div>
                                        @error('new_break_in')
                                            <p class="inputErrorMessage">{{ $message }}</p>
                                        @enderror
                                        @error('new_break_out')
                                            <p class="inputErrorMessage">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                            @endif

                            <tr>
                                <!-- 備考入力欄 -->
                                <th>備考</th>
                                <td>
                                    <!-- ステータスが承認待ちなら書き換え禁止 -->
                                    <textarea
                                        name="comment"
                                        class="textareaRemarksField"
                                        {{ $isPending ? 'disabled' : '' }}
                                    >{{ old('comment', $correctionRequest->comment ?? '') }}</textarea>

                                    @error('comment')
                                        <p class="inputErrorMessage">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 承認待ちでないなら修正ボタン押下可能 -->
                    @if(!$isPending)
                        <div class="formActionsPanel">
                            <button type="submit" class="submitUpdateButton">修正</button>
                        </div>
                    @endif
                </form>

                @if($isPending)
                    <div class="approvalPendingWrapper">
                        <p class="approvalPendingMessage">*承認待ちのため修正はできません。</p>
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
