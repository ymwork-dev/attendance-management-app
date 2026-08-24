<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Api\V1\AttendanceBreakResource;
use App\Http\Resources\Api\V1\ApplicationResource;

// APIレスポンス用のJSONデータに変換する機能を継承した勤怠データクラス
class AttendanceRecordResource extends JsonResource
{
    /**
     * APIレスポンス用のJSONデータをdataキーで包む
     */
    public static $wrap = 'data';

    /**
     * APIレスポンス用のJSONデータに変換する処理を宣言
     *
     * @param Request $request 画面からのリクエストデータが入った箱
     * @return array 整形されたレスポンスデータの配列
     */
    public function toArray(Request $request): array
    {
        // JSONデータとして内容を配列で返す
        // (ID・スタッフID・日付・出勤・退勤・スタッフデータ・休憩・修正申請)
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'total_time' => $this->total_time,
            'total_break_time' => $this->total_break_time,
            'comment' => $this->comment,
            'breaks' => AttendanceBreakResource::collection($this->whenLoaded('breaks')),
            'applications' => ApplicationResource::collection($this->whenLoaded('applications')),
        ];
    }

}
