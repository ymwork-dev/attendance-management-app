<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\BreakLog;
// 日時取得計算機能
use Carbon\Carbon;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T13_AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        // 管理者用ダミーデータ作成
        $admin = User::factory()->create(['admin_status' => true]);
        // スタッフ用ダミーデータ作成
        $user = User::factory()->create(['name' => '詳細テスト太郎']);
        // 本日の日付をcarbonオブジェクトで年月日形式で取得
        $today = Carbon::today()->format('Y-m-d');

        // ダミーの勤怠データを作成
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => $today . ' 09:00:00',
            'clock_out' => $today . ' 18:00:00',
            'comment' => 'テスト用備考サンプル',
        ]);

        // ダミーの休憩データを作成
        BreakLog::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 管理者でログインして指定したIDの勤怠詳細画面を表示できるか検証
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSee('詳細テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト用備考サンプル');
    }

    #[Test]
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $attendance = AttendanceRecord::factory()->create();

        // 管理者ログインで指定したIDの管理者用勤怠詳細画面にアクセスし、修正時刻のエラーを検証
        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '18:00',
            'clock_out' => '09:00',
            'comment' => '出勤時間エラーテスト',
        ]);

        // 勤怠詳細画面を再表示し、指定したエラーメッセージが表示されることを検証
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    #[Test]
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $attendance = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            // 既存の休憩データはなく、休憩データを作成し設定
            'breaks' => [
                [
                    'id' => null,
                    'break_in' => '19:00',
                    'break_out' => '20:00',
                ],
            ],
            'comment' => '休憩開始エラーテスト',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'breaks.0.break_in' => '休憩時間が不適切な値です'
        ]);
    }

    #[Test]
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $attendance = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'id' => null,
                    'break_in' => '12:00',
                    'break_out' => '19:00',
                ],
            ],
            'comment' => '休憩終了エラーテスト',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    #[Test]
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $attendance = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }
}
