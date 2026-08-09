<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * 会員登録
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make(
            $input,
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class),
                ],

                'password' => $this->passwordRules(),
            ],
            [
                'name.required' => '名前を入力してください。',
                'name.max' => '名前は255文字以内で入力してください。',

                'email.required' => 'メールアドレスを入力してください。',
                'email.email' => 'メールアドレスの形式で入力してください。',
                'email.max' => 'メールアドレスは255文字以内で入力してください。',
                'email.unique' => 'このメールアドレスはすでに登録されています。',

                'password.required' => 'パスワードを入力してください。',
                'password.confirmed' => '確認用パスワードと一致していません。',
            ]
        )->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
