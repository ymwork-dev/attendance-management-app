<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreakLog extends Model
{
    // テスト用ダミーデータを作成
    use HasFactory;

    // 実際のテーブル名を指定
    protected $table = 'breaks';

    // 1回分の休憩データ一式を一括保存する項目の指定
    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];
}
