<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// 管理者用ページが、管理者以外からアクセスされたときに守られているかを確認するテスト
class T21_AdminRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 管理者ではないスタッフが管理者用勤怠一覧にアクセスすると403になる(): void
    {
        // 管理者ではない(admin_statusがfalse)スタッフを作成
        $staff = User::factory()->create(['admin_status' => false]);

        // スタッフとしてログインした状態で、管理者用ページへ直接アクセス
        $response = $this->actingAs($staff)->get(route('admin.attendance.list'));

        // アクセス拒否(403)になることを確認
        $response->assertStatus(403);
    }

    #[Test]
    public function 管理者は管理者用勤怠一覧にアクセスできる(): void
    {
        // 管理者(admin_statusがtrue)を作成
        $admin = User::factory()->create(['admin_status' => true]);

        // 管理者としてログインした状態で、管理者用ページへアクセス
        $response = $this->actingAs($admin)->get(route('admin.attendance.list'));

        // 正常に表示されることを確認
        $response->assertStatus(200);
    }
}
