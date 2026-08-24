<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * attendance_recordsテーブルにcommentカラムを追加
     *
     * @return void 戻り値なし
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('clock_out');
        });
    }

    /**
     * commentカラムを削除
     *
     * @return void 戻り値なし
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};