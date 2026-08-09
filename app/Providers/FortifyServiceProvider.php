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
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * サービス登録
     */
    public function register(): void
    {
        // ログアウト後はログイン画面へ遷移
        $this->app->instance(
            LogoutResponseContract::class,
            new class implements LogoutResponseContract
            {
                public function toResponse($request)
                {
                    return redirect('/login');
                }
            }
        );
    }

    /**
     * Fortify設定
     */
    public function boot(): void
    {
        // Fortifyの各処理を登録
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(
            UpdateUserProfileInformation::class
        );
        Fortify::updateUserPasswordsUsing(
            UpdateUserPassword::class
        );
        Fortify::resetUserPasswordsUsing(
            ResetUserPassword::class
        );

        // 既存ログイン画面を使用
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 既存会員登録画面を使用
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン試行回数を制限
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input(
                Fortify::username()
            );

            return Limit::perMinute(5)->by(
                Str::transliterate(
                    Str::lower($email).'|'.$request->ip()
                )
            );
        });
    }
}
