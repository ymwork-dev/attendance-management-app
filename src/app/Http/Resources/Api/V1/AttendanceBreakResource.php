<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// APIレスポンス用のJSONデータに変換する機能を継承した勤怠休憩クラス
class AttendanceBreakResource extends JsonResource
{
    /**
     * APIレスポンスの内容を返す処理を宣言
     *
     * @param Request $request 画面からのリクエストデータが入った箱
     * @return array 整形されたレスポンスデータの配列
     */
    public function toArray(Request $request): array
    {
        // ID・休憩開始・休憩終了をJSONデータとして配列で返す
        return [
            'id' => $this->id,
            'break_in' => $this->break_in,
            'break_out' => $this->break_out,
        ];
    }
}
