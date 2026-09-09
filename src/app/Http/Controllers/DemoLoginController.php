<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// パスワード入力なしで固定のデモアカウントにログインさせるコントローラー(設置)
class DemoLoginController extends Controller
{
    /**
     * デモ用ログイン用の固定デモアカウント(役割 => メールアドレス)
     *
     * @var array<string, string>
     */
    private const DEMO_ACCOUNTS = [
        'employee' => 'demo@example.com',
        'admin'    => 'test@example.com',
    ];

    /**
     * 指定された役割の固定デモアカウントへ、パスワード確認なしでログインする
     *
     * @param Request $request リクエスト情報
     * @param string $role ログインする役割(employee / admin)
     * @return RedirectResponse 役割に応じた初期画面へのリダイレクト
     */
    public function store(Request $request, string $role): RedirectResponse
    {
        // 役割の妥当性はルート側(whereIn)で保証済み。念のため未定義なら404
        $email = self::DEMO_ACCOUNTS[$role] ?? abort(404);

        // 固定のデモアカウントを取得(シーダー未実行なら元のログイン画面へ戻す)
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->with('demo_login_failed', 'デモアカウントが見つかりません。シーダーを実行してください。');
        }

        // パスワード確認なしでログインし、セッションIDを再発行する
        Auth::login($user);
        $request->session()->regenerate();

        // 管理者は管理者用の初期画面へ
        if ($role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        // スタッフ用の入り口から入った目印を残す(FortifyServiceProviderと揃える)
        $request->session()->put('login_entrance', 'staff');

        return redirect()->route('attendance.index');
    }
}
