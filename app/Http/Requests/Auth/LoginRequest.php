<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    /**
     * ログインValidation
     */
    public function rules(): array
    {
        $username = Fortify::username();

        return [
            $username => [
                'required',
                'string',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * 日本語エラーメッセージ
     */
    public function messages(): array
    {
        $username = Fortify::username();

        return [
            "{$username}.required" => 'メールアドレスを入力してください。',
            "{$username}.string" => 'メールアドレスを正しく入力してください。',
            "{$username}.email" => 'メールアドレスの形式で入力してください。',
            'password.required' => 'パスワードを入力してください。',
            'password.string' => 'パスワードを正しく入力してください。',
        ];
    }
}
