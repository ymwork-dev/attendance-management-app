<?php

use Illuminate\Database\Migrations\Migration;
// laravel標準のデーターベーステーブル構造の設計図機能を読み込み
use Illuminate\Database\Schema\Blueprint;
// laravel標準のデーターベーステーブルの操作をする機能の読み込み
use Illuminate\Support\Facades\Schema;

// マイグレーション機能を継承した名前のないクラス(設置)を返す
return new class extends Migration {

    /**
     * 新しくstamp_correction_requestsテーブルを作成
     *
     * @return void 戻り値なし
     */
    public function up(): void {
        // 勤怠修正申請のテーブルを設計を作成して操作
        // (勤怠修正申請情報、ユーザー情報、勤怠情報、承認待ち・承認済み情報、備考、日時)
        Schema::create('stamp_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_record_id')->constrained()->onDelete('cascade');
            $table->time('requested_clock_in')->nullable();
            $table->time('requested_clock_out')->nullable();
            $table->json('requested_breaks')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * stamp_correction_requestsテーブルを削除）
     *
     * @return void 戻り値なし
     */
    public function down(): void {
        // 勤怠修正申請を削除するための機能
        Schema::dropIfExists('stamp_correction_requests');
    }
};
