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

class T18_ApiAttendanceWriteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 勤怠が作成される(): void
    {
        $user = User::factory()->create();
        // 作成したユーザーをAPI認証でログイン状態にする
        Sanctum::actingAs($user, ['*'], 'sanctum');

        // テストで送信する勤怠データの準備
        $postData = [
            'date' => '2026-06-30',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ];

        // APIへ勤怠データ登録のリクエストを送る(登録処理はコントローラーで実行される)
        $response = $this->postJson('/api/v1/attendance-records', $postData);

        // 勤怠データが作成されることを検証
        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-06-30',
        ]);
    }

    #[Test]
    public function バリデーションエラー時に422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();
        // 作成したユーザーをAPI認証でログイン状態にする
        Sanctum::actingAs($user, ['*'], 'sanctum');

        // APIへ空データ登録のリクエストを送る
        $response = $this->postJson('/api/v1/attendance-records', []);

        // 422(入力不備)とにエラーが返るか検証(日本語エラーメッセージはvalidation.phpで定義)
        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function 勤怠が更新される(): void
    {
        $user = User::factory()->create();
        // 作成したユーザーをAPI認証でログイン状態にする
        Sanctum::actingAs($user, ['*'], 'sanctum');
        // テスト用勤怠データを作成して、作成したユーザーデータに紐づける
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // テスト用更新データを準備
        $updateData = [
            'date' => $record->date,
            'clock_in' => '10:00:00',
        ];

        // 勤怠データ更新リクエストをAPIに送信
        $response = $this->putJson("/api/v1/attendance-records/{$record->id}", $updateData);

        // APIが正しく処理されたことを検証
        $response->assertStatus(200);
        // 勤怠データがデータベースで更新されたことを検証
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in' => '10:00:00',
        ]);

        // 存在しないIDで勤怠データの更新リクエストをAPIに送信
        $notFoundResponse = $this->putJson('/api/v1/attendance-records/99999', $updateData);
        // 404(見つからない)を返すことを検証
        $notFoundResponse->assertStatus(404);
    }

    #[Test]
    public function 勤怠が削除される(): void
    {
        $user = User::factory()->create();
        // 作成したユーザーをAPI認証でログイン状態にする
        Sanctum::actingAs($user, ['*'], 'sanctum');
        // テスト用勤怠データを作成して、作成したユーザーデータに紐づける
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // APIへ勤怠データの削除リクエストを送信(処理自体はコントローラで実行)
        $response = $this->deleteJson("/api/v1/attendance-records/{$record->id}");

        // 削除成功204を返し、勤怠データが削除されることを検証
        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);

        // 存在しないIDで勤怠データの削除リクエストをAPIに送信
        $notFoundResponse = $this->deleteJson('/api/v1/attendance-records/99999');
        // 404(見つからない)を返すことを検証
        $notFoundResponse->assertStatus(404);
    }
}
