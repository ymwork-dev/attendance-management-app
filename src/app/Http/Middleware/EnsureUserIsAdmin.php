<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// ログイン中のユーザーが管理者かどうかを確認するミドルウェア
class EnsureUserIsAdmin
{
    /**
     * 管理者でなければ403エラーを返し、管理者なら次の処理へ進める
     *
     * @param Request $request リクエスト情報
     * @param Closure $next 次に実行する処理
     * @return Response レスポンス
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ログイン中のユーザーが管理者(admin_statusがtrue)でなければアクセス拒否
        if (!Auth::check() || !Auth::user()->admin_status) {
            abort(403);
        }

        // 管理者の場合は、そのまま次の処理へ進める
        return $next($request);
    }
}
