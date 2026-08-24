<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Validator;

// laravelのFortifyをつかったCreatesNewUserを実装したクラス
class CreateNewUser implements CreatesNewUsers
{
    /**
     * ユーザー情報を新規作成
     *
     * @param array $input ユーザーの入力データ
     * @return User 安全に作成されたデータ
     */
    public function create(array $input): User
    {
        // 新規会員登録のリクエストを受付
        $request = new RegisterRequest();

        // 入力内容のバリデーションを実行。ルール違反があれば設定したエラーメッセージを表示
        Validator::make($input, $request->rules(), $request->messages())->validate();

        // パスワードは安全に暗号化し、ユーザー情報を新規登録
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
