<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// (Userモデル)を使うための読み込み
use App\Models\User;
// laravel標準機能のパスワード暗号化機能を使うための読み込み
use Illuminate\Support\Facades\Hash;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// テスト機能を継承したオリジナルログインテスト機能を作成するための関数(設置)
class T02_UserLoginTest extends TestCase
{
    // テスト機能実行時にデーターベースを初期化する
    use RefreshDatabase;

    // 日本語関数を使用したテスト
    #[Test]
    // メールアドレスが未入力の場合、エラーが出るか検証するテスト関数(機能)
    public function 一般ユーザー_メールアドレスが未入力の場合バリデーションメッセージが表示される()
    {
        //メールアドレスと暗号化したパスワードをテストユーザー用に1件作成する
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('password123')]);

        // ログイン画面へメールアドレスのみ空欄でデータを送信
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        // メールアドレスを入力してください というエラーがあるか検証
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    #[Test]
    // スワードが未入力の場合エラーが出るか検証するテスト機能
    public function 一般ユーザー_パスワードが未入力の場合バリデーションメッセージが表示される()
    {
        // メールアドレスと暗号化パスワードをテスト用に1件作成
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('password123')]);

        // パスワードのみ空欄でデータを送信
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        // パスワードを入力してください というエラーがあるか検証
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    #[Test]
    // 登録内容と一致しない場合、エラーが出るか検証するテスト機能
    public function 一般ユーザー_登録内容と一致しない場合バリデーションメッセージが表示される()
    {
        // メールアドレスと暗号化パスワードをテスト用に1件作成
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('password123')]);

        // 作成したテスト用メールアドレスとは異なるデータで送信
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        // ログイン情報が登録されていません というエラーがあるか検証
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
