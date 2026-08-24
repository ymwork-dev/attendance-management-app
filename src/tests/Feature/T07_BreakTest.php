<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
// laravel標準機能のデーターベース情報取得機能を使うための読み込み
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// TestCaseを継承したオリジナルな休憩ボタンテスト機能を作成するクラス(設置)
class T07_BreakTest extends TestCase
{
    // テスト実行時に時に初期化する
    use RefreshDatabase;

    // 日本語関数を使用したテスト
    #[Test]
    // 憩ボタンが正しく機能するか検証する関数(機能)
    public function 休憩ボタンが正しく機能する()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データを作成
        // (ユーザー情報、今日の日付、
        // 今現在の出勤時刻を取得しセット、退勤時刻は空欄)
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 休憩入ボタンが表示されるか検証
        $response->assertSee('休憩入');

        // テスト用ユーザーをログイン状態のまま、休憩ボタン押下(データ送信)
        $this->actingAs($user)->post('/attendance/break');

        // テスト用ユーザーをログイン状態のまま、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 画面に休憩中が表示されることを検証
        $response->assertSee('休憩中');
    }

    #[Test]
    // 休憩は1日に何回もできることを検証する機能
    public function 休憩は一日に何回でもできる()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データを作成
        // (ユーザー情報、今日の日付、
        // 今現在の出勤時刻を取得しセット、退勤時刻は空欄)
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // スト用休憩中データーを直接データーベースに登録(素早く本題テスト実行するため一回目の休憩データ登録)
        // (勤怠登録データー取得、30分前休憩入時刻をセット、15分前の休憩戻時刻をセット
        // データ作成日時、データ更新日時)
        DB::table('breaks')->insert([
            'attendance_record_id' => $attendance->id,
            'break_in' => Carbon::now()->subMinutes(30)->toTimeString(),
            'break_out' => Carbon::now()->subMinutes(15)->toTimeString(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 1回休憩していても画面に休憩入ボタンが表示されるか検証
        $response->assertSee('休憩入');
    }

    #[Test]
    // 休憩戻ボタンが正しく機能するか検証する関数(機能)
    public function 休憩戻ボタンが正しく機能する()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データを作成
        // (ユーザー情報、今日の日付、今現在の出勤時刻をセット、退勤時刻は空欄)
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // テスト用休憩中データーを直接データーベースに登録(素早く本題テスト実行するため)
        // (勤怠データー、今現在の出勤時刻をセット、休憩戻は空欄、
        // データー作成日時、データー更新日時)
        DB::table('breaks')->insert([
            'attendance_record_id' => $attendance->id,
            'break_in' => Carbon::now()->toTimeString(),
            'break_out' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 画面に休憩戻ボタンが表示されるか検証
        $response->assertSee('休憩戻');

        // テスト用ユーザーをログイン状態のまま、休憩戻ボタンを押下(データを送信)
        $this->actingAs($user)->post('/attendance/break');

        // テスト用ユーザーをログイン状態のまま、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 画面ステータスに出勤中が表示されるか検証
        $response->assertSee('出勤中');
    }

    #[Test]
    // 休憩戻は1日に何回でもできることを検証する機能
    public function 休憩戻は一日に何回でもできる()
    {
        // テスト用ユーザを作成(出勤中データの作成)
        $user = User::factory()->create();
        // テスト用勤怠データーを作成
        // (ユーザ情報、今日日付、今現在の出勤時刻、退勤時刻は空欄)
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // テスト用休憩データを直接データーベースに登録(素早く本題テスト実行するため1回目の休憩済データ登録)
        // (勤怠登録データー、30分間の休憩入データセット、15分前の休憩戻データをセット
        // データ作成日時、データ更新日時)
        DB::table('breaks')->insert([
            'attendance_record_id' => $attendance->id,
            'break_in' => Carbon::now()->subMinutes(30)->toTimeString(),
            'break_out' => Carbon::now()->subMinutes(15)->toTimeString(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // テスト用休憩データを直接データベースに登録(素早く本題テスト実行するため２回目の休憩入を登録)
        // (勤怠登録データ、今現在の休憩入時刻、退勤時刻は空欄、
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
        // 画面に休憩戻ボタンが表示されることを検証
        $response->assertSee('休憩戻');
    }

    #[Test]
    // 休憩時刻が勤怠一覧画面で確認できることを検証する機能
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        // 今現在の時刻を(2026年6月6日9時)固定
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 9, 0, 0));
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用ユーザーをログイン状態にし、出勤ボタンを押下(データ送信)
        $this->actingAs($user)->post('/attendance/clock-in');

        // 今現在時刻を(2026年6月6日12時)固定
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 12, 0, 0));
        // テスト用ユーザーをログイン状態のまま、休憩ボタンを押下(データ送信)
        $this->actingAs($user)->post('/attendance/break');

        // 今現在時刻を(2026年6月6日13時)固定
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 13, 0, 0));
        // テスト用ユーザーをログイン状態のまま、休憩戻ボタン押下(データ送信)
        $this->actingAs($user)->post('/attendance/break');

        // テスト用ユーザーをログイン状態のまま、勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list');
        // 画面に休憩時刻が表示されることを検証
        $response->assertSee('01:00');

        // 時刻の固定を解除し、今現在時刻に戻す
        Carbon::setTestNow();
    }
}
