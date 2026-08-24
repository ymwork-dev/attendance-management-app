<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// TestCaseを継承したオリジナルな出勤ボタンテスト機能を作成するクラス(設置)
class T06_ClockInTest extends TestCase
{
    // テスト実行時に初期化する
    use RefreshDatabase;

    // 日本語関数を使用したテスト
    #[Test]
    // 出勤ボタンが正しく機能するか検証するための関数(機能)
    public function 出勤ボタンが正しく機能する()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 出勤ボタンが表示されていることを確認
        $response->assertSee('出勤');

        // 出勤ボタンを押下(データ送信)を再現
        $this->actingAs($user)->post('/attendance/clock-in');

        // 出金ボタン押下後、再度勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // ボタンが出勤中に切り替わることを検証
        $response->assertSee('出勤中');
    }

    #[Test]
    // 出勤は1日1回を検証するための機能
    public function 出勤は一日一回のみできる()
    {
        // テスト用ユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データを作成
        // (ユーザー情報、今日の日付、
        // 8時間前の出勤時刻を取得しセット、今現在の退勤時刻を取得しセット)
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(8)->toTimeString(),
            'clock_out' => Carbon::now()->toTimeString(),
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        // 出勤ボタンが表示されないことを検証
        $response->assertDontSee('<button type="submit" class="clock-in-btn">出勤</button>', false);
    }

    #[Test]
    // 出勤時刻が勤怠一覧画面で確認できることを検証する機能
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        // テスト用に今現在の日時をUI形式で取得し固定(朝の9：00に固定にし時間のバグを回避)
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 9, 0, 0));
        // テスト用ユーザ－を1件作成
        $user = User::factory()->create();

        // テスト用ユーザーをログイン状態にし、出勤ボタン押下(データ送信)
        $this->actingAs($user)->post('/attendance/clock-in');

        // テスト用ユーザーをログイン状態のまま、勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list');
        // 画面に9時出勤が表示されることを検証
        $response->assertSee('09:00');

        // 固定した時刻を解除し、今現在の時刻に戻す
        Carbon::setTestNow();
    }
}
