<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
// laravel標準機能の日時取得計算機能(Carbon)を使うための読み込み
use Carbon\Carbon;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// TestCaseを継承したオリジナル日時取得機能を作成するためのクラス(設置)
class T04_DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    // 日本語関数を使用したテスト
    #[Test]
    // 現在の日情報がUIと同じ形式で出力されているか検証する関数(機能)
    // (ユーザーがパソコンやスマホの画面で実際に見たり入力したりする形式)
    public function 現在の日時情報がUIと同じ形式で出力されている()
    {
        // テスト用ユーザーを1件作成し、テスト用現在時刻を取得
        $user = User::factory()->create();
        Carbon::setTestNow(Carbon::create(2026, 6, 6, 15, 30, 0));

        // テスト用ユーザーでログイン状態にし、勤怠登録画面を表示
        $response = $this->actingAs($user)->get('/attendance');

        // 画面に日時がUI形式で出力されているか検証
        $response->assertSee('2026年6月6日');
        $response->assertSee('15:30');

        Carbon::setTestNow();
    }
}
