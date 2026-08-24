<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    /**
     * リクエストの実行権限をチェック
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを定義
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     */
    public function messages()
    {
        return [
            'per_page.max' => '100以下の数値を指定してください。',
        ];
    }
}