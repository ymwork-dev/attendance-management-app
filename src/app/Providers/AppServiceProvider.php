<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

// laravelの基本機能を継承したアプリの機能設定を提供するクラス
class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションのサービスを登録
     *
     * @return void 戻り値なし
     */
    public function register(): void
    {
        //
    }

    /**
     * アプリケーションの起動時の初期設定
     *
     * @return void 戻り値なし
     */
    public function boot(): void
    {
        // ログインしていないユーザーの移動先を/loginに固定
        Authenticate::redirectUsing(function ($request) {
            return route('login');
        });

        // メール認証していないユーザーがログインしようとした時の移動先をメール認証誘導画面に固定
        EnsureEmailIsVerified::redirectTo('email/verify');
    }
}
