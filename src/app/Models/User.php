<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
// laravel標準の沢山のエロクワントリレーション機能(1対多)を使うための読み込み
use Illuminate\Database\Eloquent\Relations\HasMany;
// API用の認証システムトークン
use Laravel\Sanctum\HasApiTokens;

// 勤怠管理のユーザーにメール認証機能をするためのクラス(設置)
class User extends Authenticatable implements MustVerifyEmail
{
    // テストデータの大量生産と通知機能を組合わせる
    use HasApiTokens, HasFactory, Notifiable;

    // 名前とメールアドレス、パスワードはユーザーは書き換え可能
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'admin_status',
    ];

    // パスワードと自動ログインはガードされている(ヒドゥン)
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * データの形式を正しく変換するための関数(機能)
     *
     * @return array キャスト設定の配列
     */
    protected function casts(): array
    {
        // メール認証の日時を日付形式に、パスワードは暗号化に変換して返す
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin_status'      => 'boolean',
        ];
    }

    /**
     * 1対多のリレーションを使うための関数(機能)
     *
     * @return HasMany 勤怠データとの1対多のリレーション関係
     */
    public function attendanceRecords(): HasMany
    {
        // ユーザーは複数存在する勤怠データ(1対多のリレーション)を引っ張ってくる
        return $this->hasMany(AttendanceRecord::class, 'user_id');
    }

    /**
     * ユーザーがメール認証を完了しているか、または管理者であるかを判定
     *
     * @return bool 認証済み（または管理者）の場合はtrue
     */
    public function hasVerifiedEmail(): bool
    {
        // 管理者ユーザーはメール認証を常に完了済みにする
        if ($this->admin_status === true) {
            return true;
        }
        return ! is_null($this->email_verified_at);
    }
}
