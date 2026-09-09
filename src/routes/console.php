<?php

use Illuminate\Foundation\Inspiring; // 表示するメッセージを用意するため
use Illuminate\Support\Facades\Artisan; // artisanコマンドを使うための読み込み
use Illuminate\Support\Facades\Schedule; // 定期実行を登録するための読み込み

// artisanコマンド(インスパイア)を作成
Artisan::command('inspire', function (){
    // メッセージを表示
    $this->comment(Inspiring::quote());
// コマンドの説明を設定
})->purpose('Display an inspiring quote');

// デモ環境を毎日早朝に初期化する(サーバー側 crontab への schedule:run 登録が前提)
Schedule::command('demo:reset --force')->dailyAt('04:00');
