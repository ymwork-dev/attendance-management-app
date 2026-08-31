<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// 日本語の関数のためシステムにテストだと認識させる目印を読み込み
use PHPUnit\Framework\Attributes\Test;

// 申請詳細(読み取り専用の履歴確認)画面のテストを行うクラス
class T22_StampCorrectionRequestDetailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 自分の申請詳細には申請内容と状態が表示される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        $requestData = StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'requested_clock_in' => '10:00:00',
            'requested_clock_out' => '19:00:00',
            'status' => 'approved',
            'comment' => '自分の申請の備考',
        ]);

        $response = $this->actingAs($user)->get(route('stamp_correction_request.detail', $requestData->id));

        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('自分の申請の備考');
        $response->assertSee('承認済み');
    }

    #[Test]
    public function 他人の申請詳細は閲覧できない(): void
    {
        $owner = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $owner->id]);

        $requestData = StampCorrectionRequest::create([
            'user_id' => $owner->id,
            'attendance_record_id' => $record->id,
            'status' => 'pending',
            'comment' => '他人の申請',
        ]);

        // 申請とは無関係の別ユーザー
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('stamp_correction_request.detail', $requestData->id));

        // 自分の申請ではないため404になることを確認
        $response->assertStatus(404);
    }

    #[Test]
    public function 却下された申請の詳細には却下済みと表示される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        $requestData = StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'status' => 'rejected',
            'comment' => '却下された申請',
        ]);

        $response = $this->actingAs($user)->get(route('stamp_correction_request.detail', $requestData->id));

        $response->assertStatus(200);
        $response->assertSee('却下済み');
    }
}
