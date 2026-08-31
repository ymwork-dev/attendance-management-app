<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakLog;
use App\Models\StampCorrectionRequest;
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

    #[Test]
    public function 承認待ちの修正申請がある場合出退勤と休憩の入力欄が読み取り専用になる(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-06-11',
            'clock_in'  => '2026-06-11 09:00:00',
            'clock_out' => '2026-06-11 18:00:00',
        ]);

        // この勤怠データに対する、承認待ちの修正申請を作成
        StampCorrectionRequest::create([
            'user_id'              => $user->id,
            'attendance_record_id' => $record->id,
            'requested_clock_in'   => '09:00:00',
            'requested_clock_out'  => '18:00:00',
            'status'               => 'pending',
            'comment'              => '承認待ちの申請',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        $response->assertStatus(200);

        // 出退勤の入力欄が読み取り専用になっていることを確認
        $response->assertSee('name="clock_in" class="inputTimeField" value="09:00" readonly', false);
        $response->assertSee('name="clock_out" class="inputTimeField" value="18:00" readonly', false);
    }

    #[Test]
    public function 却下された場合は申請中の表示に戻らず元の勤怠データが表示される(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-06-11',
            'clock_in'  => '2026-06-11 09:00:00',
            'clock_out' => '2026-06-11 18:00:00',
        ]);

        // 却下済みの修正申請(承認待ちではない)
        StampCorrectionRequest::create([
            'user_id'              => $user->id,
            'attendance_record_id' => $record->id,
            'requested_clock_in'   => '10:00:00',
            'requested_clock_out'  => '19:00:00',
            'status'               => 'rejected',
            'comment'              => '却下された申請',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        $response->assertStatus(200);

        // 却下された申請の内容ではなく、元の確定した勤怠データの時刻が入力欄に表示されることを確認
        $response->assertSee('name="clock_in" class="inputTimeField" value="09:00"', false);
        $response->assertSee('name="clock_out" class="inputTimeField" value="18:00"', false);

        // 入力欄が readonly になっていない(申請中扱いになっていない)ことを確認
        $response->assertDontSee('name="clock_in" class="inputTimeField" value="09:00" readonly', false);
    }

    #[Test]
    public function 承認済みの申請がある場合は新しい備考欄に前回の内容が残らない(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id'   => $user->id,
            'clock_in'  => '2026-06-11 09:00:00',
            'clock_out' => '2026-06-11 18:00:00',
        ]);

        // 承認済みの修正申請(過去の申請の備考)
        StampCorrectionRequest::create([
            'user_id'              => $user->id,
            'attendance_record_id' => $record->id,
            'status'               => 'approved',
            'comment'              => '過去に承認された申請の備考',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $record->id));

        $response->assertStatus(200);

        // 過去の申請の備考が、新しい申請フォームの初期値として残っていないことを確認
        $response->assertDontSee('過去に承認された申請の備考');
    }
}
