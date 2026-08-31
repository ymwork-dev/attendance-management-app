<?php

namespace App\Models;

// データーベーステーブルとPHPを結びつけ操作する機能の読み込み
use Illuminate\Database\Eloquent\Model;
// 1対多のつながりで子から親データーを紐づけるリレーション機能の読み込み
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// 1対多のつながりで親から子データー一覧を紐づけるリレーション機能の読み込み
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

// モデル機能を継承した勤怠登録データを扱うためのクラス
class AttendanceRecord extends Model
{
    // テスト用ダミーデータを作成
    use HasFactory;

    // 安全に一括保存を許可するカラムの指定
    // total_time・total_break_timeはデータベースに列が無く、下記のアクセサで計算して返す値なのでここには含めない
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
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

    /**
     * 休憩時間の合計を計算する(秒単位)
     *
     * @return int 休憩時間の合計(秒)
     */
    private function totalBreakSeconds(): int
    {
        return $this->breaks->sum(function (BreakLog $break): int {
            // 休憩開始または休憩終了が未入力の休憩は0秒として扱う
            if (!$break->break_in || !$break->break_out) {
                return 0;
            }
            return Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
        });
    }

    /**
     * 休憩時間の合計を「時:分」形式で取得するアクセサ
     * $record->total_break_time でアクセスできる(データベースに同名の列は無い)
     *
     * @return string 休憩時間の合計(例: "01:00")
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $totalBreakSeconds = $this->totalBreakSeconds();

        return sprintf('%02d:%02d', intdiv($totalBreakSeconds, 3600), intdiv($totalBreakSeconds % 3600, 60));
    }

    /**
     * 実働時間(出勤〜退勤から休憩時間を差し引いた時間)を「時:分」形式で取得するアクセサ
     * $record->total_time でアクセスできる(データベースに同名の列は無い)
     *
     * @return string 実働時間(例: "08:00")。出勤または退勤が未登録の場合は空文字
     */
    public function getTotalTimeAttribute(): string
    {
        // 出勤・退勤どちらかが未登録の場合は計算できないため空文字を返す
        if (!$this->clock_in || !$this->clock_out) {
            return '';
        }

        $totalWorkSeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out))
            - $this->totalBreakSeconds();

        // 休憩時間の登録内容によっては計算結果がマイナスになる可能性があるため、0未満にならないよう補正
        $totalWorkSeconds = max(0, $totalWorkSeconds);

        return sprintf('%02d:%02d', intdiv($totalWorkSeconds, 3600), intdiv($totalWorkSeconds % 3600, 60));
    }
}
