<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// laravel標準装備のServiceProviderを継承したオリジナルのFortifyServiceProviderを作成するた目のクラス(設置)
class FortifyServiceProvider extends ServiceProvider
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
     * アプリケーション起動時の初期設定
     *
     * @return void 戻り値なし
     */
    public function boot(): void
    {
        // 会員登録画面が起動したら自動で会員登録画面を表示する
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面が起動したら自動でログイン画面を表示する
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 未認証時に「メール認証誘導画面」へ遷移させる設定
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // 会員登録ボタンが押されたら、ユーザーの新規作成をするクラス
        Fortify::createUsersUsing(CreateNewUser::class);
        // プロフィールの更新ボタンが押されたら、会員情報を書き換えるクラス
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        // パスワードが更新ボタンが押されたら、ユーザーのパスワード情報を更新するクラス
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        // パスワードがリセットボタンが押されたら、ユーザーのパスワード情報をリセットするクラス
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        // 2段階認証が設定されているユーザーを、専用画面へ移動させる設定
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // ログイン認証チェックで5回間違えるとロックがかかる
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // ログイン時に２段階認証を設定している場合、5回間違えるとロックがかかる
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // パスキー認証(顔や指紋の認証)を1分間に10回間違えた場合ロックがかかる
        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });

        // ログイン認証のカスタマイズ処理
        Fortify::authenticateUsing(function (Request $request) {
           // スタッフユーザーまたは管理者を探す
            $user = User::where(Fortify::username(), $request->input(Fortify::username()))->first();

            // パスワードが一致しているかチェック
            if ($user && Hash::check($request->input('password'), $user->password)) {

               // もしログインした画面がスタッフ用画面からの場合
                if (!$request->is('admin/*') && !$request->is('admin')) {
                    // スタッフ用の入り口から入った目印を刻む
                    $request->session()->put('login_entrance', 'staff');
                }

                // 認証成功としてユーザーを返す
                return $user;
            }

            return null;
        });
    }
}

