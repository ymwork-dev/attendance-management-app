<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// 管理者用一覧画面のテストを行うクラス
class T12_AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 遷移した際に現在の日付が表示される(): void
    {
        // 管理者ステータスのテスト用データを作成
        $admin = User::factory()->create(['admin_status' => true]);

        // 管理者用一覧画面へアクセス
        $response = $this->actingAs($admin)->get(route('admin.attendance.list'));

        // 正常に表示することを確認
        $response->assertStatus(200);

        // 本日の日付を('YYYY年M月D日')形式で表示することを確認
        $todayString = Carbon::today()->isoFormat('YYYY年M月D日');
        $response->assertSee($todayString);
    }

    #[Test]
    public function 前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);

        // 前日の日付を検索用のY-m-d形式で取得
        $previousDay = Carbon::today()->subDay()->format('Y-m-d');
        // 前日の日付を画面表示用のYYYY年M月D日形式で取得
        $previousDayDisplay = Carbon::today()->subDay()->isoFormat('YYYY年M月D日');

        // 理者用一覧画面へアクセス
        $response = $this->actingAs($admin)->get(route('admin.attendance.list', ['date' => $previousDay]));

        // 正常に表示することを確認
        $response->assertStatus(200);
        // 前日の勤怠情報が表示されることを確認
        $response->assertSee($previousDayDisplay);
    }

    #[Test]
    public function 翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);

        // 翌日の日付を検索用のY-m-d形式で取得
        $nextDay = Carbon::today()->addDay()->format('Y-m-d');
        // 翌日の日付を画面表示用のYYYY年M月D日型式で取得
        $nextDayDisplay = Carbon::today()->addDay()->isoFormat('YYYY年M月D日');

        // 管理者用一覧画面へアクセス
        $response = $this->actingAs($admin)->get(route('admin.attendance.list', ['date' => $nextDay]));

        // 正常に表示されることを確認
        $response->assertStatus(200);
        // 翌日の勤怠情報が表示されることを確認
        $response->assertSee($nextDayDisplay);
    }

    #[Test]
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);

        // テスト用ユーザーデータを作成
        $user1 = User::factory()->create(['name' => 'テスト太郎']);
        $user2 = User::factory()->create(['name' => 'テスト次郎']);

        // 本日の日付をY-m-d形式で取得
        $today = Carbon::today()->format('Y-m-d');

        // テスト用ユーザー1勤怠データを作成
        AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in' => $today . ' 09:00:00',
            'clock_out' => $today . ' 18:00:00',
        ]);

        // テスト用ユーザー2勤怠データを作成
        AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'date' => $today,
            'clock_in' => $today . ' 10:00:00',
            'clock_out' => $today . ' 19:00:00',
        ]);

        // 管理者用一覧画面へアクセス
        $response = $this->actingAs($admin)->get(route('admin.attendance.list'));

        // 正常に表示しすることを確認
        $response->assertStatus(200);
        // ユーザー1・ユーザー２の勤怠データが表示されることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('テスト次郎');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }
}
