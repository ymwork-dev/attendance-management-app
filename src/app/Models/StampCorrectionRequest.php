<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 1対多のつながりで子から親データーを紐づけるリレーション機能の読み込み
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// モデル機能を継承した勤怠修正申請を扱うためのクラス
class StampCorrectionRequest extends Model
{
    // 安全に一括保存(複数の代入)を許可するカラムの指定(ユーザー情報、勤怠データー、ステータス、備考)
    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'requested_clock_in',
        'requested_clock_out',
        'requested_breaks',
        'comment',
        'status',
    ];

    // 休憩申請データを配列扱いに変換する設定
    protected $casts = [
        'requested_breaks' => 'array',
    ];

    /**
     * この修正申請を提出したスタッフユーザーのデータを取得
     *
     * @return BelongsTo 従業員ユーザーとのリレーション関係
     */
    public function user(): BelongsTo
    {
        // 修正申請(子)からをユーザー(親)へ紐づけ
        return $this->belongsTo(User::class);
    }

    /**
     * この修正申請の対象となっている親の勤怠レコードを取得
     *
     * @return BelongsTo 勤怠レコードとのリレーション関係
     */
    public function attendanceRecord(): BelongsTo
    {
        // 修正申請から勤怠データーへ紐づけ
        return $this->belongsTo(AttendanceRecord::class);
    }
}
