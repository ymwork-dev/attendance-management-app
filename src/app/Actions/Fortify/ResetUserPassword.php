<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

// パスワードのリセット処理を実装するクラス
class ResetUserPassword implements ResetsUserPasswords
{
    // 共有のパスワードルールを定義したトレイトを使用
    use PasswordValidationRules;

    /**
     * ユーザーのパスワードをリセットする
     *
     * @param User $user パスワードを変更するユーザーのデータ
     * @param array $input パスワード変更時の入力データ
     * @return void 戻り値なし
     */
    public function reset(User $user, array $input): void
    {
       // ActionsではRequestクラスを利用できない為ここで直接バリデーションを実施
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // パスワードを暗号化して保存する
        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
