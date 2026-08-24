<?php

// laravel標準機能のFeature機能(テスト機能)を使うための読み込み
namespace Tests\Feature;

// laravel標準機能のテスト機能実行時にデーターベースをリフレッシュする機能の読み込み
use Illuminate\Foundation\Testing\RefreshDatabase;
// テスト機能の基本機能の呼び出し
use Tests\TestCase;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
// (laravelのテスト機能はPHPUnitシステムをベースに動く)
use PHPUnit\Framework\Attributes\Test;

// テスト機能(TestCase)を継承した(RegisterTest)オリジナルテスト機能を作成するためのクラス(設置)
class T01_RegisterTest extends TestCase
{
    // テスト実効時にデーターベースを初期化する
    use RefreshDatabase;

    // 日本語関数を使用したテスト
    #[Test]
    // 名前が未入力のときにエラーが出るか検証するテスト関数(機能)
    public function 名前が未入力の場合バリデーションメッセージが表示される()
    {
        // 会員登録画面で名前のみ空欄のデータを送信
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        //お名前を入力してください というエラーがあるか検証
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    #[Test]
    // メールアドレスが未入力のときにエラーがでるか検証するテスト関数(機能)
    public function メールアドレスが未入力の場合バリデーションメッセージが表示される()
    {
        //メールアドレスのみ空欄でデータを送信
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // メールアドレスを入力してください というエラーがあるか検証
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    #[Test]
    // パスワードが8文字未満の場合エラーが出るか検証するテスト関数(機能)
    public function パスワードが8文字未満の場合バリデーションメッセージが表示される()
    {
        // パスワードが8文字以上という要件だけを満たしていないデータを送信
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'pass7',
            'password_confirmation' => 'pass7',
        ]);

        // パスワードは8文字以上で入力してください というエラーがあるか検証
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    #[Test]
    // 確認用パスワードが一致していない場合エラーが出るか検証するテスト関数(機能)
    public function パスワードが一致しない場合バリデーションメッセージが表示される()
    {
        // 確認用パスワードが不一致という要件だけが満たしていないデータを送信
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        // パスワードと一致しません というエラーがあるか検証
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    #[Test]
    // パスワードが未入力の場合エラーが出るか検証するテスト関数(機能)
    public function パスワードが未入力の場合バリデーションメッセージが表示される()
    {
        // パスワードと確認用パスワードを空欄でデータを送信
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        // パスワードを入力してください というエラーがあるか検証
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    #[Test]
    // 会員登録画面に全項目正しく入力されたとき
    // データが保存されるか検証するテスト関数(機能)
    public function フォームに内容が入力されていた場合データが正常に保存される()
    {
        // すべての入力要項を満たしてデータを送信
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 名前とメールアドレスがusersデーターベースに登録されることを検証
        // パスワードは暗号化されて保存されるため検証からは除外
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
    }
}
