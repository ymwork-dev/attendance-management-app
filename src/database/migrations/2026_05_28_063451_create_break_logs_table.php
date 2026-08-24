<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// マイグレーションを実行するための新しいクラス(設置)
return new class extends Migration
{
    /**
     * 新しくbreaksテーブルを作成
     *
     * @return void 戻り値なし
     */
    public function up(): void
    {
        // 休憩テーブルを作成するための設計指示
        // 休憩テーブルにしまうカラムの定義
        // 休憩テーブルのID、勤怠管理テーブルID(従業員テーブルと紐づけるための外部キー)、
        // 休憩入戻時刻(戻時刻は休憩入時は空っぽでOK)、休憩入戻打刻時の日時形式
        Schema::create('breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();
            $table->time('break_in');
            $table->time('break_out')->nullable();
            $table->timestamps();
        });
    }

    /**
     * breaksテーブルを削除
     *
     * @return void 戻り値なし
     */
    public function down(): void
    {
        // マイグレーション(部屋)を削除するときは休憩テーブル(棚)もまとめて消す
        Schema::dropIfExists('breaks');
    }
};
