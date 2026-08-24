<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
//日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

//TestCaseを継承したオリジナルな退勤ボタンテスト機能を作成するクラス(設置)
class T08_ClockOutTest extends TestCase
{
    //テスト実行時に時に初期化する
    use RefreshDatabase;

    //日本語関数を使用したテスト
    #[Test]

    //退勤ボタンが正しく機能するか検証する関数(機能)
    public function 退勤ボタンが正しく機能する()
    {
        //テスト用ユーザーを1件作成
        $user = User::factory()->create();
        //テスト用勤怠データを作成
        //(ユーザー情報、今日の日付、
        // 今現在の出勤時刻を取得しセット、退勤時刻は空欄)
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        //テスト用ユーザーをログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        //退勤ボタンが表示されるか検証
        $response->assertSee('退勤');

        //テスト用ユーザーをログイン状態のまま、退勤ボタン押下(データ送信)
        $this->actingAs($user)->post('/attendance/clock-out');

        //テスト用ユーザーをログイン状態のまま、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');
        //画面ステータスに退勤済が表示されることを検証
        $response->assertSee('退勤済');
    }

    #[Test]
    //退勤時刻が勤怠一覧画面で確認できるか検証する関数(機能)
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        //今現在の時刻を(2026年6月6日9時)固定
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 9, 0, 0));
        //テスト用ユーザーを1件作成
        $user = User::factory()->create();
        //テスト用ユーザーをログイン状態にし、出勤ボタンを押下(データ送信)
        $this->actingAs($user)->post('/attendance/clock-in');

        //今現在時刻を(2026年6月6日18時)固定
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 18, 0, 0));
        //テスト用ユーザーをログイン状態のまま、退勤ボタンを押下(データ送信)
        $this->actingAs($user)->post('/attendance/clock-out');

        //テスト用ユーザーをログイン状態のまま、勤怠一覧画面を表示
        $response = $this->actingAs($user)->get('/attendance/list');
        //画面に退勤時刻が表示されることを検証
        $response->assertSee('18:00');

        //時刻の固定を解除し、今現在時刻に戻す
        Carbon::setTestNow();
    }
}
