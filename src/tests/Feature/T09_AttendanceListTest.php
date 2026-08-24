<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

// laravel標準のテスト基本機能を継承した独自の勤怠一覧テスト機能を作成するクラス(設置)
class T09_AttendanceListTest extends TestCase
{
    // テスト機能実行時に初期化
    use RefreshDatabase;

    // テスト機能の準備をするための関数(機能)
    protected function setUp(): void
    {
        // テスト用ユーザー情報を作成してセット
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // 自分が行った勤怠情報が全て表示されているか検証する関数(機能)
    public function test_自分が行った勤怠情報が全て表示されている(): void
    {
        //  テスト用の今日の年月日を取得してセット
        Carbon::setTestNow(Carbon::parse('2026-06-09'));

        // テスト用勤怠登録データーを作成
        // (ユーザー情報、今日の日付、出勤時刻9時、退勤時刻18時)
        $record = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-06-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // テスト用ユーザーをログイン状態にし、勤怠一覧画面を表示
        $response = $this->actingAs($this->user)->get(route('attendance.list'));

        // 画面に出勤日、詳細リンクが表示されることを検証(200は画面表示の検証)
        $response->assertStatus(200);
        $response->assertSee('06/09');
        $response->assertSee('詳細');
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示されることを検証する機能
    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        // テスト用今日の年月日をセット
        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        // テスト用ユーザーをログイン状態にし、勤怠一覧画面を表示
        $response = $this->actingAs($this->user)->get(route('attendance.list'));

        // 画面に現在の年月が表示されることを検証
        $response->assertStatus(200);
        $response->assertSee('2026/06');
    }

    // 前月を押下した時に表示月の前月の情報が表示されるを検証する機能
    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        // テスト用今日の年月日をセット
        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        // テスト用ユーザーをログイン状態にし、まず今月の勤怠一覧画面を表示
        // 今月の画面が正常に表示したか確認
        $response = $this->actingAs($this->user)->get(route('attendance.list', ['month' => '2026-06']));
        $response->assertStatus(200);

        // 画面にある前月リンクボタン押下
        $nextResponse = $this->get(route('attendance.list', ['month' => '2026-05']));

        // 移動後の画面に前月の情報が表示されることを検証
        $nextResponse->assertStatus(200);
        $nextResponse->assertSee('2026/05');
    }

    // 翌月を押下した時に表示月の翌月の情報が表示されることを検証する機能
    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        // テスト用今日の年月日をセット
        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        // テスト用ユーザーをログイン状態にし、まず今月の勤怠一覧画面を表示
        $response = $this->actingAs($this->user)->get(route('attendance.list', ['month' => '2026-06']));
        $response->assertStatus(200);

        // 画面にある翌月リンクボタン押下
        $nextResponse = $this->get(route('attendance.list', ['month' => '2026-07']));

        // 移動後の画面に翌月の情報が表示されることを検証
        $nextResponse->assertStatus(200);
        $nextResponse->assertSee('2026/07');
    }

    // 詳細を押下するとその日の勤怠詳細画面に遷移することを検証する機能
    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        // テスト用勤怠登録データーを作成
        // (ユーザー情報、現在の年月日)
        $record = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        // テスト用ユーザーをログイン状態にし、まず勤怠一覧画面を表示して詳細ボタンを確認
        $response = $this->actingAs($this->user)->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('詳細');

        // 画面にある詳細リンクボタン押下
        //どの日の詳細データーかわかるよう、作成した勤怠登録データーを渡す
        $nextResponse = $this->get(route('attendance.detail', ['id' => $record->id]));

        // 移動後、勤怠詳細画面が表示されるか検証
        $nextResponse->assertStatus(200);
    }
}
