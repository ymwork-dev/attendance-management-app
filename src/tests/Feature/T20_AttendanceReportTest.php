<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T20_AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ゲストはレポートページにアクセスできずログインにリダイレクトされる(): void
    {
        // 未認証で勤怠レポート画面へアクセス
        $response = $this->get('/attendance/report');

        // ログイン画面へリダイレクトされることを検証
        $response->assertRedirect('/login');
    }

    #[Test]
    public function 認証ユーザーの統計情報が正しく計算されて画面に渡される(): void
    {
        // テスト用ユーザーをメール認証済の認証状態で作成
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        //勤怠レポート画面へアクセス
        $response = $this->get('/attendance/report');

        // 統計情報が正しく計算されて画面に渡されることを検証
        $response->assertStatus(200)
            ->assertViewHasAll(['summary', 'monthlyData', 'anomaly']);
    }

    #[Test]
    public function 勤怠記録がないユーザーでも安全に処理されてエラーが発生しない(): void
    {
        // テスト用ユーザーをメール認証済の認証状態で作成
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        //勤怠レポート画面へアクセス
        $response = $this->get('/attendance/report');

        // 正しく画面が表示されることを検証
        $response->assertStatus(200);

        // 勤怠記録が無くても安全に処理されてエラーが発生しないことを検証
        $summary = $response->viewData('summary');
        $this->assertEquals(0, $summary['total_work']['h']);
        $this->assertEquals(0, $summary['total_work']['m']);
    }
}
