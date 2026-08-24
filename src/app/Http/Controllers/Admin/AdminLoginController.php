<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class AdminLoginController extends Controller
{
    /**
     * 管理者ログイン画面を表示
     *
     * @return View 管理者ログイン画面のビュー
     */
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    /**
     * 管理者ログインの認証処理を行う
     *
     * @param AdminLoginRequest $request ログイン情報
     * @return RedirectResponse 勤怠一覧画面へリダイレクト
     * @throws ValidationException 認証に失敗した場合
     */
    public function login(AdminLoginRequest $request): RedirectResponse
    {
        // メールアドレスとパスワードを取得
        $credentials = $request->only('email', 'password');

        // 管理者ユーザーのみ認証対象とする
        $credentials['admin_status'] = true;

        // 管理者として認証し、成功したら勤怠一覧画面へ遷移
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.attendance.list');
        }

        // 認証に失敗した場合、メッセージを表示
        throw ValidationException::withMessages([
            'login_failed' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * 管理者のログアウト処理を行う
     *
     * @param Request $request リクエスト情報
     * @return RedirectResponse 管理者ログイン画面へリダイレクト
     */
    public function logout(Request $request): RedirectResponse
    {
        // 管理者アカウントからログアウト
        Auth::logout();

        // セッションを無効化し、トークンを再生成する(CSRFトークンは正規画面から送信されたことを証明する秘密の文字列)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 管理者ログイン画面へリダイレクト
        return redirect()->route('admin.login');
    }
}
