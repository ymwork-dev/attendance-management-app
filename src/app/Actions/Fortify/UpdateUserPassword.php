<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

// パスワード更新ルールを実装するクラス
class UpdateUserPassword implements UpdatesUserPasswords
{
    // パスワードルールを共有するトレイト
    use PasswordValidationRules;

    /**
     * ユーザーのパスワードを更新する
     *
     * @param User $user パスワードを変更するユーザーのデータ
     * @param array $input パスワード変更時の入力データ
     * @return void 戻り値なし
     */
    public function update(User $user, array $input): void
    {
        //  ActionsではRequestクラスを利用できないため、ここで直接バリデーションを行う
        // 現在のパスワードの入力必須、登録済みのパスワードとの一致を確認
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            // 現在のパスワードと一致しない場合、「入力されたパスワードが現在のパスワードと一致しません」というエラーメッセージを返す
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        //一致した場合はパスワードを暗号化して保存する
        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
