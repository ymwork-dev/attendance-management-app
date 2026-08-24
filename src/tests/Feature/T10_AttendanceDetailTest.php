<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
// テスト機能の基本機能の呼び出し
use Tests\TestCase;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// 勤怠詳細画面のテストを行うクラス
class T10_AttendanceDetailTest extends TestCase
{
    // テスト実行時にデータベースを初期化
    use RefreshDatabase;

    // 日本語メソッド名を使用したテスト
    #[Test]
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);

        // テスト用勤怠データを作成
        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-06-11',
            'clock_in'  => '2026-06-11 09:00:00',
            'clock_out' => '2026-06-11 18:00:00',
        ]);

        // 勤怠詳細画面へアクセス
        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        // 正常表示され、ユーザー名が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
    }

    #[Test]
    public function 勤怠詳細画面の日付が選択した日付になっている(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-06-11',
            'clock_in'  => '2026-06-11 09:00:00',
            'clock_out' => '2026-06-11 18:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        // 正常表示され、選択した日付が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('2026年');
        $response->assertSee('6月11日');
    }

    #[Test]
    public function 出勤退勤にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-06-11',
            'clock_in'  => '2026-06-11 09:15:00',
            'clock_out' => '2026-06-11 18:45:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        // 正常に表示され、ログインユーザーの打刻と一致する出退勤時刻が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('09:15');
        $response->assertSee('18:45');
    }

    #[Test]
    public function 休憩にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-06-11',
            'clock_in'  => '2026-06-11 09:00:00',
            'clock_out' => '2026-06-11 18:00:00',
        ]);

        // テスト用休憩データを作成
        BreakLog::create([
            'attendance_record_id' => $record->id,
            'break_in'             => '2026-06-11 12:15:00',
            'break_out'            => '2026-06-11 13:45:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        // 正常に表示され、ログインユーザーの打刻と一致する休憩時刻が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('12:15');
        $response->assertSee('13:45');
    }
}
