<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// APIレスポンス用のJSONデータに変換する機能を継承したスタッフデータクラス
class UserResource extends JsonResource
{
    /**
     * APIレスポンス用に返す処理を宣言
     *
     * @param Request $request 画面からのリクエストデータが入った箱
     * @return array 整形されたレスポンスデータの配列
     */
    public function toArray(Request $request): array
    {
        // JSONデータとして内容を配列で返す
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
