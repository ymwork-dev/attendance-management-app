<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T14_AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        // 管理者のテスト用ダミーデータを作成
        $admin = User::factory()->create(['admin_status' => true]);
        // 2件のテスト用ダミーデータを作成
        User::factory()->create(['name' => 'スタッフ太郎', 'email' => 'taro@example.com']);
        User::factory()->create(['name' => 'スタッフ次郎', 'email' => 'jiro@example.com']);

        // 管理者ログインして管理者用スタッフ一覧画面を表示
        $response = $this->actingAs($admin)->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee('スタッフ太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee('スタッフ次郎');
        $response->assertSee('jiro@example.com');
    }

    #[Test]
    public function 選択したユーザーの勤怠情報が正しく表示される(): void
    {
        // 管理者のテスト用ダミーデータを作成
        $admin = User::factory()->create(['admin_status' => true]);
        // スタッフのテスト用ダミーデータを作成
        $user = User::factory()->create();
        // 本日の日付をcarbonで年月日形式に変換して取得
        $today = Carbon::today()->format('Y-m-d');

        // テスト用勤怠データを作成
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $today,
        ]);

        // 管理者ログインして管理者用スタッフ別勤怠一覧画面を表示
        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', ['id' => $user->id]));

        // 取得した年が正しく表示されるか検証
        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->format('Y'));
    }

    #[Test]
    public function 前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        // 本日の日付を取得し、carbonで前月の年月を変換
        $lastMonth = Carbon::today()->subMonth()->format('Y-m');

        // 管理者ログインして管理者用スタッフ別勤怠一覧画面を表示
        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'id' => $user->id,
            'month' => $lastMonth
        ]));

        $response->assertStatus(200);
    }

    #[Test]
    public function 翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        $nextMonth = Carbon::today()->addMonth()->format('Y-m');

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'id' => $user->id,
            'month' => $nextMonth
        ]));

        $response->assertStatus(200);
    }

    #[Test]
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $attendance = AttendanceRecord::factory()->create();

        // 管理者ログインして管理者用スタッフ別勤怠詳細画面を表示
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
    }
}
