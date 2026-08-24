<?php

namespace Database\Factories;

use App\Models\User;
// laravel標準のFactory(テスト機能)を使うための読み込み
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
// laravel標準のStr(文字列Stringをランダム生成したりする機能)を使うための読み込み
use Illuminate\Support\Str;

// laravel標準装備のFactory機能を継承したオリジナルのダミーデーターを作成するためのクラス(設置)
class UserFactory extends Factory
{
    // パスワードの暗号化処理を、一時的に保存しておく変数(箱)
    protected static ?string $password;

    /**
     * モデルのデフォルトのダミー状態（データ構造）を定義
     *
     * @return array ダミーデータの配列
     */
    public function definition(): array
    {
        // ダミーデータには、名前とメール認証したメールアドレス、暗号化したパスワード、ログイン保持トークン(ランダム英数字10字)を定義する
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'admin_status' => false,
        ];
    }

    /**
     * メール未認証状態のダミーデータを生成する設定を定義
     *
     * @return static ファクトリのインスタンス
     */
    public function unverified(): static
    {
        // メール認証日時を空っぽに書き換える
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
