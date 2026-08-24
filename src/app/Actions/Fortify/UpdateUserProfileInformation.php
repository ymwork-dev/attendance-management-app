<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

// プロフィール更新処理を実装するクラス
class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * ユーザーのプロフィール情報を更新する
     *
     * @param User $user プロフィールを変更するユーザーのデータ
     * @param array $input 名前やメールアドレス変更時の入力データ
     * @return void 戻り値なし
     */
    public function update(User $user, array $input): void
    {
        // ActionsではRequestクラスを利用できないため、ここで直接プロフィール更新ルールを作成
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // 他ユーザーとのメールアドレス重複不可、自分のメールアドレスは許可
                Rule::unique('users')->ignore($user->id),
            ],
        // プロフィール更新用のバリデーションを実行
        ])->validateWithBag('updateProfileInformation');

        // メールアドレスが変更され、メール認証必要な場合は認証状態をリセット
        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
         // メール認証のリセットが不要な場合はプロフィール情報を更新
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * メール認証が必要なユーザーのプロフィールを更新する
     *
     * @param User $user プロフィールを変更するユーザーのデータ
     * @param array $input 新しい名前やメールアドレスの入力データ
     * @return void 戻り値なし
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        // 名前とメールアドレスを更新し、メール認証状態をリセット
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        // 新しいメールアドレスに認証メールを送る
        $user->sendEmailVerificationNotification();
    }
}
