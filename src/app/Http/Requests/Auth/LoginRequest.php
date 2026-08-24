<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     *バリデーションを実行できるよう許可する
    */
    public function authorize(): bool
    {
        //誰でも許可
        return true;
    }

    /**
     * 入力内容のバリデーションルールを定義する
     */
    public function rules(): array
    {
        //メールアドレスとパスワードは入力必須、文字列であること
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * バリデーションメッセージを定義する
     */
    public function messages(): array
    {
        return [
            // 未入力の場合
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }

    /**
     * ユーザーのログイン認証処理を行う
     *
     * @return void 戻り値なし
     * @throws ValidationException 認証失敗時のバリデーションエラー
     */
    public function authenticate(): void
    {
        // 認証情報の取得
        $credentials = $this->only('email', 'password');

        // ログインに失敗した場合
        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}
