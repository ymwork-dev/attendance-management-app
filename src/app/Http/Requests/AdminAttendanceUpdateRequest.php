<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * リクエストの実行権限をチェック
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
        // 出勤時刻・退勤時刻・備考は必須、休憩時刻は空欄可
        return [
            'clock_in'  => ['required'],
            'clock_out' => ['required'],
            'breaks'    => ['nullable', 'array'],
            'comment'   => ['required'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     */
    public function messages(): array
    {
        return [
            'clock_in.required'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.required'   => '備考を記入してください',
        ];
    }

    /**
     * バリデーション後に出退勤時間と休憩時間の不整合をチェック）
     *
     * @param mixed $validator バリデータインスタンス
     * @return void
     */
    protected function withValidator($validator): void
    {
        // 出勤時刻と退勤時刻を時刻形式（H:i）に変換
        $validator->after(function (Validator $validator): void {
            $clockIn  = $this->filled('clock_in') ? Carbon::parse($this->input('clock_in'))->format('H:i') : null;
            $clockOut = $this->filled('clock_out') ? Carbon::parse($this->input('clock_out'))->format('H:i') : null;

            // 出勤時刻が退勤時刻より遅い場合はエラー
            if (filled($clockIn) && filled($clockOut)) {
                if ($clockIn > $clockOut) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // 複数の休憩入力データを時刻形式(H:i)に変換
            if ($this->has('breaks') && is_array($this->input('breaks'))) {
                // 各休憩時間の入力内容をチェック
                collect($this->input('breaks'))->each(function ($breakData, $index) use ($validator, $clockIn, $clockOut) {
                    $breakIn  = filled($breakData['break_in'] ?? null) ? Carbon::parse($breakData['break_in'])->format('H:i') : null;
                    $breakOut = filled($breakData['break_out'] ?? null) ? Carbon::parse($breakData['break_out'])->format('H:i') : null;

                    // 休憩開始時刻が勤務時間外の場合はエラー
                    if (filled($breakIn)) {
                        if ((filled($clockIn) && $breakIn < $clockIn) ||
                            (filled($clockOut) && $breakIn > $clockOut)) {

                            $validator->errors()->add(
                                'breaks.' . $index . '.break_in',
                                '休憩時間が不適切な値です'
                            );

                            // Collectionのeach内では、returnがforeachでのcontinueと同じ役割（次のデータの処理へ進む）になります
                            return;
                        }
                    }

                    // 休憩終了時刻が勤務時間外の場合はエラー
                    if (filled($breakOut)) {
                        if ((filled($clockIn) && $breakOut < $clockIn) ||
                            (filled($clockOut) && $breakOut > $clockOut)) {

                            $validator->errors()->add(
                                'breaks.' . $index . '.break_out',
                                '休憩時間もしくは退勤時間が不適切な値です'
                            );
                        }

                        // 休憩終了時刻が、休憩開始時刻より前の場合エラー
                        if (filled($breakIn) && $breakOut < $breakIn) {

                            $validator->errors()->add(
                                'breaks.' . $index . '.break_out',
                                '休憩時間が不適切な値です'
                            );
                        }
                    }
                });
            }

            // 追加の休憩入力データを時刻形式(H:i)に変換
            $newBreakIn  = $this->filled('new_break_in') ? Carbon::parse($this->input('new_break_in'))->format('H:i') : null;
            $newBreakOut = $this->filled('new_break_out') ? Carbon::parse($this->input('new_break_out'))->format('H:i') : null;

            // 追加休憩開始時刻が勤務時間外の場合はエラー
            if (filled($newBreakIn)) {
                if ((filled($clockIn) && $newBreakIn < $clockIn) ||
                    (filled($clockOut) && $newBreakIn > $clockOut)) {

                    $validator->errors()->add(
                        'new_break_in',
                        '休憩時間が不適切な値です'
                    );
                }
            }

            // 追加休憩終了時刻が勤務時間外の場合エラー
            if (filled($newBreakOut)) {
                if ((filled($clockIn) && $newBreakOut < $clockIn) ||
                    (filled($clockOut) && $newBreakOut > $clockOut)) {

                    $validator->errors()->add(
                        'new_break_out',
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            // 追加休憩終了時刻が休憩開始時刻より前の場合はエラー
            if (filled($newBreakIn) &&
                filled($newBreakOut) &&
                $newBreakOut < $newBreakIn) {

                $validator->errors()->add(
                    'new_break_out',
                    '休憩時間が不適切な値です'
                );
            }
        });
    }
}
