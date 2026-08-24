<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

class T17_ApiAttendanceReadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 勤怠一覧がJSONで取得できる(): void
    {
        // テスト用スタッフユーザーを1件作成
        $user = User::factory()->create();
        // テスト用勤怠データを3件作成し、作成したユーザーに紐づける
        AttendanceRecord::factory()->count(3)->create(['user_id' => $user->id]);

        // 勤怠データ取得を取得するため、APIにリクエスト送る
        $response = $this->getJson('/api/v1/attendance-records');

        // JSON形式で勤怠一覧データが、正しく表示することを検証
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function 勤怠詳細がJSONで取得できる(): void
    {
        $user = User::factory()->create();
        // テスト用勤怠データを作成し、作成したスタッフユーザーに紐づける
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        // 勤怠詳細データを取得するため、APIにリクエストを送る
        $response = $this->getJson("/api/v1/attendance-records/{$record->id}");

        // JSON形式で勤怠詳細データが、正しく表示することを検証
        $response->assertStatus(200)
            ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',

                'user' => [
                    'id',
                    'name',
                ],

                'breaks' => [
                    '*' => [
                        'id',
                        'break_in',
                        'break_out',
                    ],
                ],

                'applications' => [
                    '*' => [
                        'id',
                    ],
                ],
            ]
        ]);
    }

    #[Test]
    public function 存在しないIDでは404とエラーJSONが返る(): void
    {
        // 存在しない勤怠IDでAPIにリクエストを送る
        $response = $this->getJson('/api/v1/attendance-records/99999');

        // 404エラーと、エラーメッセージがJSONで返ることを検証
        $response->assertStatus(404)
            ->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }
}
