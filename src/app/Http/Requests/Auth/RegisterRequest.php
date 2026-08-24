<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     *バリデーションを実行できるよう許可する
     */
    public function authorize(): bool
    {
        // 誰でも許可
        return true;
    }

    /**
     * 会員登録時のバリデーションルールを定義
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     */
    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',

            'email.email' => 'メールアドレスはメール形式で入力してください',

            'email.unique' => 'このメールアドレスは既に登録されています。',

            'password.min' => 'パスワードは8文字以上で入力してください',

            'password.confirmed' => 'パスワードと一致しません',
        ];
    }
}
