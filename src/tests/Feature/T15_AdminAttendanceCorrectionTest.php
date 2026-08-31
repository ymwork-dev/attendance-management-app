<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T15_AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 承認待ちの修正申請が全て表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        // テスト用で2件のスタッフの勤怠データを作成
        $attendance1 = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        $attendance2 = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // 承認待ちの修正申請データの作成
        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance1->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'comment' => '承認待ちの申請データ',
        ]);
        // 承認済みの修正申請データの作成
        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance2->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'comment' => '承認済みの申請データ',
        ]);

        // 管理者ログインして管理者用申請一覧画面の承認待ちタブを表示
        $response = $this->actingAs($admin)->get(route('attendance_correction_request.index', ['tab' => 'pending']));

        // 画面表示の検証
        $response->assertStatus(200);
        $response->assertSee('承認待ちの申請データ');
        $response->assertDontSee('承認済みの申請データ');
    }

    #[Test]
    public function 承認済みの修正申請が全て表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        // テスト用で2件のスタッフの勤怠データを作成
        $attendance1 = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        $attendance2 = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // 承認待ちの修正申請データの作成
        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance1->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'comment' => '承認待ちの申請データ',
        ]);
        // 承認済みの修正申請データの作成
        StampCorrectionRequest::create([
            'attendance_record_id' => $attendance2->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'comment' => '承認済みの申請データ',
        ]);

        // 管理者ログインして管理者用申請一覧画面の承認済みタブを表示
        $response = $this->actingAs($admin)->get(route('attendance_correction_request.index', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済みの申請データ');
        $response->assertDontSee('承認待ちの申請データ');
    }

    #[Test]
    public function 修正申請の詳細内容が正しく表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        // 本日の日付と作成したテスト用スタッフの勤怠データを作成
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id,'date' => today(),]);

         // 承認待ちの修正申請データの作成
        $requestData = StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'comment' => '詳細確認用コメント',
        ]);

        // 管理者ログインして作成したスタッフの管理者用修正申請承認画面を表示
        $response = $this->actingAs($admin)->get(route('admin.request.approve', ['attendance_correct_request_id' => $requestData->id]));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('詳細確認用コメント');
    }

    #[Test]
    public function 修正申請の承認処理が正しく行われる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        // テスト用勤怠データを作成
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        // 承認待ちの修正申請データを作成
        $requestData = StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '10:00:00',
            'requested_clock_out' => '19:00:00',
            'comment' => '承認後に反映される備考',
            'status' => 'pending',
        ]);

        // 管理者ログインして作成したスタッフの管理者用修正申請承認画面を表示し、承認ボタンを押す
        $response = $this->actingAs($admin)->post(route('admin.request.approve', ['attendance_correct_request_id' => $requestData->id]), [
            'action' => 'approve'
        ]);

        // 承認後、リダイレクトで管理者用修正承認画面を表示しなおす
        $response->assertStatus(302);

        // データベースの勤怠データが修正値に反映していることを検証
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '承認後に反映される備考',
        ]);

        // データベースの勤怠データが承認済みに更新されていることを検証
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $requestData->id,
            'status' => 'approved',
        ]);
    }

    #[Test]
    public function 修正申請の却下処理が正しく行われる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();

        // 元々09:00〜18:00で登録されている勤怠データ
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 承認待ちの修正申請データを作成
        $requestData = StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '10:00:00',
            'requested_clock_out' => '19:00:00',
            'comment' => '却下されるはずの申請',
            'status' => 'pending',
        ]);

        // 管理者ログインして却下ボタンを押す
        $response = $this->actingAs($admin)->post(route('admin.request.approve', ['attendance_correct_request_id' => $requestData->id]), [
            'action' => 'reject'
        ]);

        $response->assertStatus(302);

        // 申請のステータスが却下済みになっていることを検証
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $requestData->id,
            'status' => 'rejected',
        ]);

        // 勤怠データ自体は変更されず、元の内容のままであることを検証
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    #[Test]
    public function 承認でも却下でもない操作を送るとエラーにならず元の画面に戻る(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        $requestData = StampCorrectionRequest::create([
            'attendance_record_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'comment' => 'テスト用申請',
        ]);

        // action が想定外の値の場合
        $response = $this->actingAs($admin)->post(route('admin.request.approve', ['attendance_correct_request_id' => $requestData->id]), [
            'action' => 'unknown'
        ]);

        // 500エラーにならず、リダイレクトになることを確認
        $response->assertStatus(302);

        // 申請のステータスは変わらず承認待ちのままであることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $requestData->id,
            'status' => 'pending',
        ]);
    }
}
