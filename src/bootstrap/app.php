<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// web/apiなどのルートファイルの読み込み設定
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // アプリで使うリクエストチェックの設定
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    //エラー処理の設定
    ->withExceptions(function (Exceptions $exceptions): void {
        // 権限エラーが出た時の処理
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            // APIの場合だけ処理
            if ($request->is('api/*')) {
                // JSONでエラーを返す
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。'
                ], 403);
            }
        });

        // データが見つからない時の処理
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            // APIの場合だけ処理
            if ($request->is('api/*')) {
                // JSONでエラーを返す
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。'
                ], 404);
            }
        });

        // 認証エラーが出た時の処理
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            // APIの場合だけ処理
            if ($request->is('api/*')) {
                // JSONでエラーを返す
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
