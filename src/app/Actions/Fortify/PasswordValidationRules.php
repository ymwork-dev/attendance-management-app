<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

// パスワードのバリデーションルールを他のクラスでも共有するためのトレイト
trait PasswordValidationRules
{
    /**
     * パスワードのバリデーションルールを定義
     *
     * @return array パスワードルールの設定
     */
    protected function passwordRules(): array
    {
        // パスワードの入力必須、文字列、８文字以上、確認用パスワードとの一致
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
