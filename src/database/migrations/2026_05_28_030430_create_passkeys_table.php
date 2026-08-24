<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// パスキー機能の読み込み
use Laravel\Passkeys\Passkeys;

// マイグレーションを実行するための新しいクラス(設置)
return new class extends Migration
{
    /**
     * マイグレーションを実行する
     *
     * @return void 戻り値なし
     */
    public function up(): void
    {
        // 新しくパスキーテーブルを作成するための設計指示
        // テーブルにしまうカラムの定義
        // パスキーデータのid、ユーザーID(ユーザーが削除されたら一緒に消す)、パスキーの名前、
        // デバイス固有の識別番号(ID)、JSON(暗号化されたパスキー)データー、最後にログインした日時、
        // パスキーを登録した日時と変更した更新日時を自動で記録、ユーザーID検索するための目次
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Passkeys::userModel(), 'user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    /**
     * マイグレーションテーブルを削除
     *
     * @return void 戻り値なし
     */
    public function down(): void
    {
        // パスキーテーブルもまとめて削除する設計指示
        Schema::dropIfExists('passkeys');
    }
};
