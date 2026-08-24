<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakLog;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// 勤怠情報修正機能のテストを行うクラス
class T11_AttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    // 出勤時間が退勤時間より後の場合、エラーメッセージが表示されることを検証
    #[Test]
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-06-11',
            'clock_in' => '09:00',
            'clock_out' => '18:00'
        ]);

        // 出勤時間が退勤時間より後になるテスト用データを送信
        $response = $this->actingAs($user)->patch(route('attendance.update', $record->id), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'comment' => 'テスト備考',
        ]);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    // 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示されることを検証
    #[Test]
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-06-11',
            'clock_in' => '09:00',
            'clock_out' => '18:00'
        ]);

        $break = BreakLog::create([
            'attendance_record_id' => $record->id,
            'break_in' => '12:00',
            'break_out' => '13:00'
        ]);

        // 休憩開始時間が退勤時間より後になるテスト用データを送信
        $response = $this->actingAs($user)->patch(route('attendance.update', $record->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                $break->id => [
                    'break_in' => '19:00',
                    'break_out' => '19:30',
                ],
            ],
            'remarks' => 'テスト備考',
        ]);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['breaks.' . $break->id . '.break_in' => '休憩時間が不適切な値です']);
    }

    #[Test]
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-06-11',
            'clock_in' => '09:00',
            'clock_out' => '18:00'
        ]);

        $break = BreakLog::create([
            'attendance_record_id' => $record->id,
            'break_in' => '12:00',
            'break_out' => '13:00'
        ]);

        // 休憩終了時間が退勤時間より後になるテスト用データを送信
        $response = $this->actingAs($user)->patch(route('attendance.update', $record->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                $break->id => [
                    'break_in' => '12:00',
                    'break_out' => '19:00',
                ],
            ],
            'comment' => 'テスト備考',
        ]);

         // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['breaks.' . $break->id . '.break_out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    #[Test]
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // 備考欄が未入力のテスト用データを送信
        $response = $this->actingAs($user)->patch(route('attendance.update', $record->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
        ]);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    #[Test]
    public function 修正申請処理が実行される(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // 修正申請テスト用データ送信
        $response = $this->actingAs($user)->patch(route('attendance.update', $record->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '修正申請コメント',
        ]);

        // 修正申請データがデータベースへ保存されることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'status' => 'pending',
            'comment' => '修正申請コメント',
        ]);
    }

    #[Test]
    public function 承認待ちログインユーザーが行った申請が全て表示されていること(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // テスト用修正申請データを作成
        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'status' => 'pending',
            'comment' => '承認待ち備考表示確認',
        ]);

        // 申請一覧画面へアクセス
        $response = $this->actingAs($user)->get(route('attendance_correction_request.index'));

        // 正常に表示し、承認待ちログインユーザーが行った修正申請が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('承認待ち備考表示確認');
    }

    #[Test]
    public function 承認済みに管理者が承認した修正申請が全て表示されている(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // テスト用承認済みデータを作成
        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'status' => 'approved',
            'comment' => '承認済み備考表示確認',
        ]);

        // 申請一覧画面へアクセス(管理者・ユーザー共通)
        $response = $this->actingAs($user)->get(route('attendance_correction_request.index'));

        // 正常に表示し、承認済みに管理者が承認した修正申請が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('承認済み備考表示確認');
    }

    #[Test]
    public function 各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // テスト用申請データを作成
        $request = StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'status' => 'pending',
            'comment' => '詳細遷移確認用',
        ]);

        // 申請一覧画面へアクセス
        $response = $this->actingAs($user)->get(route('attendance_correction_request.index'));

        // 正常に表示し、申請一覧画面の詳細ボタンを確認
        $response->assertStatus(200)
            ->assertSee(route('attendance.detail', $request->attendance_record_id));
        // 勤怠詳細画面にアクセスし、正常に表示することを確認
        $this->actingAs($user)
            ->get(route('attendance.detail', $request->attendance_record_id))
            ->assertStatus(200);
    }
}
