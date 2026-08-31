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

        // 休憩が無い場合、実働時間は出退勤の差そのまま(9時〜18時なので09:00)、休憩時間は00:00になることを確認
        $response->assertJsonPath('data.total_time', '09:00');
        $response->assertJsonPath('data.total_break_time', '00:00');
    }

    #[Test]
    public function 実働時間と休憩時間が休憩データを差し引いて計算される(): void
    {
        $user = User::factory()->create();

        // 09:00〜18:00勤務、12:00〜13:00に1時間休憩
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(200);
        // 9時間勤務 - 1時間休憩 = 実働8時間
        $response->assertJsonPath('data.total_time', '08:00');
        $response->assertJsonPath('data.total_break_time', '01:00');
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
