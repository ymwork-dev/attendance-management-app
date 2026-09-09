<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// デモ環境のデータを初期状態に戻すためのArtisanコマンド(設置)
class DemoReset extends Command
{
    /**
     * コマンド名と使い方
     *
     * @var string
     */
    protected $signature = 'demo:reset {--force : 確認プロンプトを表示せずに実行する}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'データベースを作り直し、デモ用の初期データを入れ直す(ポートフォリオ公開環境の定期リセット用)';

    /**
     * コマンドの実処理
     *
     * @return int 終了コード
     */
    public function handle(): int
    {
        // 全データ削除を伴うため、--force が無ければ実行前に確認する
        if (!$this->option('force') && !$this->confirm('全データを削除してデモ初期データを入れ直します。よろしいですか?')) {
            $this->info('中止しました。');
            return self::SUCCESS;
        }

        // データベースを作り直してシーダーを流す
        $this->call('migrate:fresh', [
            '--seed'  => true,
            '--force' => true,
        ]);

        $this->info('デモデータをリセットしました。');

        return self::SUCCESS;
    }
}
