<?php

namespace App\Models;

// データーベーステーブルとPHPを結びつけ操作する機能の読み込み
use Illuminate\Database\Eloquent\Model;
// 1対多のつながりで子から親データーを紐づけるリレーション機能の読み込み
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// 1対多のつながりで親から子データー一覧を紐づけるリレーション機能の読み込み
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// モデル機能を継承した勤怠登録データを扱うためのクラス
class AttendanceRecord extends Model
{
    // テスト用ダミーデータを作成
    use HasFactory;

    // 安全に一括保存を許可するカラムの指定
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'total_time',
        'total_break_time',
        'comment',
    ];

    /**
     * スタッフデータに紐づけるための機能
     *
     * @return BelongsTo 従業員ユーザーとのリレーション関係
     */
    public function user(): BelongsTo
    {
        // スタッフデータに紐づけて返す
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩情報データ一覧に紐づけるための機能
     *
     * @return HasMany 休憩情報データとの1対多のリレーション関係
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakLog::class);
    }

    /**
     * 修正申請データーに紐づけるための機能
     *
     * @return HasMany 修正申請データとの1対多のリレーション関係
     */
    public function applications(): HasMany
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }
}
