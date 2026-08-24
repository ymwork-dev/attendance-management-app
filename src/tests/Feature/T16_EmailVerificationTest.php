<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
// laravelの通知機能を使うための読み込み
use Illuminate\Support\Facades\Notification;
// laravelのメール認証機能を使うための読み込み
use Illuminate\Auth\Notifications\VerifyEmail;
// laravelの自動URL(ルート)生成機能を使うための読み込み
use Illuminate\Support\Facades\URL;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T16_EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 会員登録後_認証メールが送信される(): void
    {
        // 通知機能をフェイク化し、実際にメールが送信されないようにする
        Notification::fake();

        // 会員登録用のテストデータを作成
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // 会員登録用のルートにテストデータを送信
        $response = $this->post('/register', $userData);

        // useの中からメールアドレスがtest@example.comのユーザーを取得
        $user = User::where('email', 'test@example.com')->first();
        // ユーザーが作成されていることを検証
        $this->assertNotNull($user);

        // フェイクのメール認証通知が送信されたことを検証
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function メール認証誘導画面で認証はこちらからボタンを押下するとメール認証サイトに遷移する(): void
    {
        $user = User::factory()->create([
            // メール認証が未完了の状態でテスト用ユーザーを作成
            'email_verified_at' => null,
        ]);

        // メール認証誘導画面にアクセスし、メール認証が未完了であることを検証
        $response = $this->actingAs($user)->get('/email/verify');
        $response->assertStatus(200);

        // メール認証サイトにアクセスし、メール認証画面にリダイレクトされることを検証
        $response = $this->actingAs($user)->get('/email/go-to-mailpit');
        $response->assertStatus(302);
        $response->assertRedirect('http://localhost:8025');
    }

    #[Test]
    public function メール認証サイトのメール認証を完了すると_勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->create([
            // メール認証が未完了の状態でテスト用ユーザーを作成
            'email_verified_at' => null,
        ]);

        // Mailpitを使わずに、直接アクセスするためのlaravelのメール認証URLを作成
        $verificationUrl = URL::temporarySignedRoute(
            // 60分間有効なメール認証URLを作成
            'verification.verify',
            now()->addMinutes(60),
            // 認証URLに必要なスタッフIDと、ハッシュ化したメールアドレスを含める
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // ログイン後、メール認証URLにアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // メール認証完了後、勤怠登録画面にリダイレクトされることを検証
        $response->assertRedirect(route('attendance.index', ['verified' => 1]));
        // スタッフがメール認証済みであることを確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
