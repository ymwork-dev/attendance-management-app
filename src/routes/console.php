<?php

use Illuminate\Foundation\Inspiring; // 表示するメッセージを用意するため
use Illuminate\Support\Facades\Artisan; // artisanコマンドを使うための読み込み

// artisanコマンド(インスパイア)を作成
Artisan::command('inspire', function (){
    // メッセージを表示
    $this->comment(Inspiring::quote());
// コマンドの説明を設定
})->purpose('Display an inspiring quote');
