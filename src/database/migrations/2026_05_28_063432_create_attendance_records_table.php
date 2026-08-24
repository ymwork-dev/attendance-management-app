<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新しくattendance_recordsテーブルを作成
     *
     * @return void 戻り値なし
     */
    public function up(): void
    {
        // 勤怠管理テーブルを作成するための設計指示
        // 勤怠管理テーブルにしまうカラムの定義
        // 勤怠管理テーブルID、ユーザーID(従業員テーブルと紐づける外部キー)、
        // 勤務日・出退勤時間(出社時の退勤時間は空っぽOKでも)、打刻データ登録の変更された日時形式
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('clock_in');
            $table->time('clock_out')->nullable();
            $table->timestamps();
        });
    }

    /**
     * attendance_recordsテーブルを削除
     *
     * @return void 戻り値なし
     */
    public function down(): void
    {
        // マイグレーションを削除するときに勤怠管理テーブルもまとめて消す
        Schema::dropIfExists('attendance_records');
    }
};
