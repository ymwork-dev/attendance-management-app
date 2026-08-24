<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
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
        // メール形式のメールアドレスとパスワード必須
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}
