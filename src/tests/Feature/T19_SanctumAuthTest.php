<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
// laravel標準のAPI認証機能を使うための読み込み
use Laravel\Sanctum\Sanctum;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T19_SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 未認証時に書き込み系APIで401が返る(): void
    {
        // 未認証状態でAPIへ勤怠データ登録リクエストを送信
        $responsePost = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-06-30',
        ]);
        // 未認証401エラーを返す
        $responsePost->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        // 未認証状態でAPIへ勤怠データ更新リクエストを送信
        $responsePut = $this->putJson('/api/v1/attendance-records/1', [
            'date' => '2026-06-30',
            'clock_in' => '10:00:00',
        ]);
        // 未認証401エラーを返す
        $responsePut->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
        // 未認証状態でAPIへ勤怠データ削除リクエストを送信
        $responseDelete = $this->deleteJson('/api/v1/attendance-records/1');
        // 未認証401エラーを返す
        $responseDelete->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    #[Test]
    public function 認証済みユーザーは自分の勤怠を更新削除できる(): void
    {
        $user = User::factory()->create();
        // 作成したユーザーをAPI認証でログイン状態にする
        Sanctum::actingAs($user, ['*'], 'sanctum');
        // テスト用勤怠データを作成して、作成したユーザーデータに紐づける
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

         // 勤怠データ更新リクエストをAPIに送信
        $putResponse = $this->putJson("/api/v1/attendance-records/{$record->id}", [
            'date' => $record->date,
            'clock_in' => '10:00:00',
        ]);
        // 勤怠データが正しく更新されることを検証
        $putResponse->assertStatus(200);

        // APIへ勤怠データ削除リクエストを送信
        $deleteResponse = $this->deleteJson("/api/v1/attendance-records/{$record->id}");
        // 勤怠データの更新削除の成功を返す
        $deleteResponse->assertStatus(204);
    }

    #[Test]
    public function 他ユーザーの勤怠を更新削除しようとすると403が返る(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

         // 作成したユーザーAをAPI認証でログイン状態にする
        Sanctum::actingAs($userA, ['*'], 'sanctum');
        // テスト用勤怠データを作成して、作成したユーザーBデータに紐づける
        $recordB = AttendanceRecord::factory()->create(['user_id' => $userB->id]);
        // 勤怠データB更新リクエストをAPIに送信
        $putResponse = $this->putJson("/api/v1/attendance-records/{$recordB->id}", [
            'date' => $recordB->date,
            'clock_in' => '10:00:00',
        ]);
        // 403権限エラーを返す
        $putResponse->assertStatus(403)
            ->assertJson(['error' => 'この操作を実行する権限がありません。']);

        // 勤怠データB削除リクエストをAPIに送信
        $deleteResponse = $this->deleteJson("/api/v1/attendance-records/{$recordB->id}");
        // 403権限エラーを返す
        $deleteResponse->assertStatus(403)
            ->assertJson(['error' => 'この操作を実行する権限がありません。']);
    }
}
