<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
// 勤怠登録データのデーターベース操作機能を使うための読み込み
use App\Models\AttendanceRecord;
// laravel標準機能のデーターベース情報取得機能を使うための読み込み
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// TestCaseを継承したオリジナルステータス表示機能を作成するためのクラス(設置)
class T05_StatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    // 日本語関数を使用したテスト
    #[Test]
    // 勤務外の場合のステータス表示が正しいか検証するための関数(機能)
    public function 勤務外の場合_勤怠ステータスが正しく表示される()
    {
        // テスト用ユーザーを1件作成する
        $user = User::factory()->create();

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');

        // ステータス表示が勤務外であることを検証
        $response->assertSee('勤務外');
    }

    #[Test]
    // 出勤中の場合のステータス表示が正しいか検証するための機能
    public function 出勤中の場合_勤怠ステータスが正しく表示される()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データーの作成
        // (ユーザーID、今日の日付、出勤時刻をセット、退勤時刻は空欄)
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');

        // ステータス表示が出勤中であることを検証
        $response->assertSee('出勤中');
    }

    #[Test]
    // 休憩中のステータス表示が正しいか検証するためのテスト機能
    public function 休憩中の場合_勤怠ステータスが正しく表示される()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データーの作成
        // (ユーザーId、今日の日付、出勤時刻のセット、退勤時刻は空欄)
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // テスト用休憩中データーを直接データーベースに登録(素早く本題テスト実行するため)
        // (勤怠データー、休憩入時刻を取得しセット、休憩戻時刻は空欄、
        // データ作成日時、データ更新日時)
        DB::table('breaks')->insert([
            'attendance_record_id' => $attendance->id,
            'break_in' => Carbon::now()->toTimeString(),
            'break_out' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');

        // ステータス表示が休憩中であることを検証
        $response->assertSee('休憩中');
    }

    #[Test]
    // 退勤済の場合のステータス表示が正しいか検証する機能
    public function 退勤済の場合_勤怠ステータスが正しく表示される()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データの作成
        // (ユーザー情報、今日の日付、
        // 8時間前の出勤時刻を取得しセット、現在の退勤時刻を取得しセット)
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(8)->toTimeString(),
            'clock_out' => Carbon::now()->toTimeString(),
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');

        // ステータス表示が退勤済表示であることを検証
        $response->assertSee('退勤済');
    }
}
