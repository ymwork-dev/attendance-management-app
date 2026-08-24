<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

// 勤怠修正画面の入力内容をチェックするためのクラス
class UpdateAttendanceRequest extends FormRequest
{
    /**
     * バリデーションを実行できるよう許可する
     */
    public function authorize(): bool
    {
        // リクエスト許可
        return true;
    }

    /**
     * バリデーションルールを定義
     */
    public function rules(): array
    {
        // 勤怠修正時の入力ルール
        return [
            'clock_in'           => ['required', 'date_format:H:i'],
            'clock_out'          => ['required', 'date_format:H:i'],
            'comment'            => ['required'],
            // 休憩は複数回の登録ができるため配列として受け取る
            'breaks'             => ['nullable', 'array'],
            // 全ての休憩開始時刻が時刻形式か確認
            'breaks.*.break_in'  => ['nullable', 'date_format:H:i'],
            // 全ての休憩終了時刻が時分形式か確認
            'breaks.*.break_out' => ['nullable', 'date_format:H:i'],

            // 新規追加の休憩時間チェック
            'new_break_in'       => ['nullable', 'date_format:H:i'],
            'new_break_out'      => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     */
    public function messages(): array
    {
        return [
            'clock_in.required'              => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.date_format'           => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required'             => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format'          => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.required'               => '備考を記入してください',

            'breaks.*.break_in.date_format'  => '休憩時間が不適切な値です',
            'breaks.*.break_out.date_format' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break_in.date_format'       => '休憩時間が不適切な値です',
            'new_break_out.date_format'      => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }

    /**
     * バリデーション後の追加チェックを設定（出退勤時間と休憩時間の不整合をチェック）
     *
     * @param mixed $validator バリデータインスタンス
     * @return void
     */
    protected function withValidator($validator): void
    {
        // 通常のバリデーション完了後に追加チェックを実行
        $validator->after(function (Validator $validator): void {

            // 入力された出勤時刻をCarbon形式に変換
            $clockIn = $this->filled('clock_in')
                ? Carbon::parse($this->input('clock_in'))->format('H:i')
                : null;

            // 入力された退勤時刻をCarbon形式に変換
            $clockOut = $this->filled('clock_out')
                ? Carbon::parse($this->input('clock_out'))->format('H:i')
                : null;

            // 出勤時刻が退勤時刻より遅い場合はエラー
            if (filled($clockIn) && filled($clockOut)) {
                if ($clockIn > $clockOut) {
                    $validator->errors()->add(
                        'clock_in',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            // 既存の休憩データがある場合
            if ($this->has('breaks') && is_array($this->input('breaks'))) {
                // 休憩データを順番に1件ずつチェック
                collect($this->input('breaks'))->each(function ($breakData, $index) use ($validator, $clockIn, $clockOut) {

                    // 休憩開始時刻・休憩終了時刻が入力されているか確認
                    $hasBreakIn  = filled($breakData['break_in'] ?? null);
                    $hasBreakOut = filled($breakData['break_out'] ?? null);

                    // 入力がある場合Carbon形式に変換、未入力の場合は空
                    $breakIn = $hasBreakIn
                        ? Carbon::parse($breakData['break_in'])->format('H:i')
                        : null;
                    $breakOut = $hasBreakOut
                        ? Carbon::parse($breakData['break_out'])->format('H:i')
                        : null;

                    // エラーメッセージの重複表示を防ぐ
                    $hasError = false;

                    // 休憩開始時刻が勤務時間外ならエラー
                    if (filled($breakIn)) {
                        if ((filled($clockIn) && $breakIn < $clockIn) ||
                        (filled($clockOut) && $breakIn > $clockOut)) {

                            $validator->errors()->add(
                                'breaks.' . $index . '.break_in',
                                '休憩時間が不適切な値です'
                            );
                            // エラーありの場合trueにする
                            $hasError = true;

                            // 次のデータの処理へ進む
                            return;
                        }
                    }

                    // 休憩終了時刻が勤務時間外ならエラー
                    if (!$hasError && filled($breakOut)) {
                        if ((filled($clockIn) && $breakOut < $clockIn) ||
                            (filled($clockOut) && $breakOut > $clockOut)) {

                            $validator->errors()->add(
                                'breaks.' . $index . '.break_out',
                                '休憩時間もしくは退勤時間が不適切な値です'
                            );

                            // エラーありの場合
                            $hasError = true;
                        }
                    }

                    // 休憩終了時刻が休憩開始時刻より前ならエラー
                    if (!$hasError && filled($breakIn) && filled($breakOut) && $breakOut < $breakIn) {
                        $validator->errors()->add(
                            'breaks.' . $index . '.break_out',
                            '休憩時間が不適切な値です'
                        );

                        // エラーありの場合trueにする
                        $hasError = true;
                    }

                    // 他にエラーが無く、休憩開始だけ入力されている場合はエラー
                    if (!$hasError && $validator->errors()->isEmpty()) {
                        if ($hasBreakIn && !$hasBreakOut) {
                            $validator->errors()->add('breaks.' . $index . '.break_in', '休憩時間が不適切な値です');
                        }
                    }
                });
            }

            //新しく追加した休憩開始・休憩終了時刻が入力されているか確認
            $hasNewBreakIn  = $this->filled('new_break_in');
            $hasNewBreakOut = $this->filled('new_break_out');

            // 新しい休憩開始時刻が入力されている場合はCarbon形式に変換し、未入力なら空
            $newBreakIn = $hasNewBreakIn
                ? Carbon::parse($this->input('new_break_in'))->format('H:i')
                : null;

            // 新しい休憩終了時刻が入力されている場合はCarbon形式に変換し、未入力なら空
            $newBreakOut = $hasNewBreakOut
                ? Carbon::parse($this->input('new_break_out'))->format('H:i')
                : null;

            // 重複エラー防止フラグ
            $hasNewError = false;

            // 休憩開始時刻が、勤務時間外ならエラー
            if (filled($newBreakIn)) {
                if ((filled($clockIn) && $newBreakIn < $clockIn) ||
                    (filled($clockOut) && $newBreakIn > $clockOut)) {

                    $validator->errors()->add(
                        'new_break_in',
                        '休憩時間が不適切な値です'
                    );

                    // エラーありの場合trueにする
                    $hasNewError = true;

                    return;
                }
            }

            // 休憩終了時刻が勤務時間外ならエラー
            if (!$hasNewError && filled($newBreakOut)) {
                if ((filled($clockIn) && $newBreakOut < $clockIn) ||
                    (filled($clockOut) && $newBreakOut > $clockOut)) {

                    $validator->errors()->add(
                        'new_break_out',
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );

                    $hasNewError = true;

                    return;
                }
            }

            // 休憩終了時刻が休憩開始時刻より前ならエラー
            if (!$hasNewError && filled($newBreakIn) &&
                filled($newBreakOut) &&
                $newBreakOut < $newBreakIn) {

                $validator->errors()->add(
                    'new_break_out',
                    '休憩時間が不適切な値です'
                );

                $hasNewError = true;
            }

            // 他にエラーがなく、休憩開始だけ入力されている場合はエラー
            if (!$hasNewError && $validator->errors()->isEmpty()) {
                if ($hasNewBreakIn && !$hasNewBreakOut) {
                    $validator->errors()->add('new_break_in', '休憩時間が不適切な値です');
                }
            }
        });
    }
}

