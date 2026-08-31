<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserIsAdmin;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;

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
        // ルートで 'admin' という名前で呼び出せるように登録
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    //エラー処理の設定
    ->withExceptions(function (Exceptions $exceptions): void {
        // 権限エラーが出た時の処理
        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            // APIの場合だけ処理
            if ($request->is('api/*')) {
                // JSONでエラーを返す
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。'
                ], 403);
            }
        });

        // データが見つからない時の処理
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            // APIの場合だけ処理
            if ($request->is('api/*')) {
                // JSONでエラーを返す
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。'
                ], 404);
            }
        });

        // 認証エラーが出た時の処理
        $exceptions->render(function (AuthenticationException $e, $request) {
            // APIの場合だけ処理
            if ($request->is('api/*')) {
                // JSONでエラーを返す
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
