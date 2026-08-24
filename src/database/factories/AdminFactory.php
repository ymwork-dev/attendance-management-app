<?php

namespace Database\Factories;

use App\Models\Admin;
// ダミーデータ作成機能読み込み
use Illuminate\Database\Eloquent\Factories\Factory;
// パスワード暗号化機能読み込み
use Illuminate\Support\Facades\Hash;

// 管理者用ダミーデータ作成するクラス
class AdminFactory extends Factory
{
    // 管理者用ダミーデータが作成するモデルを指定
    protected $model = Admin::class;

    /**
     * 管理者用ダミーデータを定義
     *
     * @return array ダミーデータの配列
     */
    public function definition(): array
    {
        // 名前・メールアドレス・暗号化したパスワードのダミーデータを返す
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}
