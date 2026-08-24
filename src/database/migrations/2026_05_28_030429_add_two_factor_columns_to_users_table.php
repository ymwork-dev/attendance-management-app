<?php

// データーベースのテーブル(棚)を作成したり変更する機能を読み込む
use Illuminate\Database\Migrations\Migration;
// マイグレーションファイルの設計図を読み込む
use Illuminate\Database\Schema\Blueprint;
// テーブル作成や削除の実行をする機能の読み込み
use Illuminate\Support\Facades\Schema;

// マイグレーションを実行するための新しいクラス(設置)
return new class extends Migration
{
    /**
     * テーブルに2段階認証用カラムを追加
     *
     * @return void 戻り値なし
     */
    public function up(): void
    {
        // usersテーブルのパスワード項目の後に２段階認証項目を追加してtable変数(箱)にいれる設計指示
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')
                ->after('password')
                ->nullable();

            // ２段階認証項目の後にリカバリーコードの項目を追加してtable変数(箱)に入れる設計指示
            $table->text('two_factor_recovery_codes')
                ->after('two_factor_secret')
                ->nullable();

            // リカバリーコードの項目の後に、2段階認証実施の日時形式をtable変数(箱)に入れる設計指示
            $table->timestamp('two_factor_confirmed_at')
                ->after('two_factor_recovery_codes')
                ->nullable();
        });
    }

    /**
     * 追加した2段階認証用カラムを削除
     *
     * @return void 戻り値なし
     */
    public function down(): void
    {
        // 2段階認証、リカバリーコード、日時形式の項目をusersテーブルから削除する設計指示
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
