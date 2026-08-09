<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    /**
     * ログイン済みユーザーを許可
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 書籍登録の入力ルール
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'author' => [
                'required',
                'string',
                'max:255',
            ],

            'isbn' => [
                'required',
                'digits:13',
                Rule::unique('books', 'isbn'),
            ],

            'published_date' => [
                'required',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'image_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'genres' => [
                'required',
                'array',
                'min:1',
            ],

            'genres.*' => [
                'integer',
                'distinct',
                Rule::exists('genres', 'id'),
            ],
        ];
    }

    /**
     * 日本語エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルを正しく入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名を入力してください。',
            'author.string' => '著者名を正しく入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.required' => 'ISBNを入力してください。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',

            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '有効な出版日を入力してください。',

            'description.string' => '説明を正しく入力してください。',

            'image_url.url' => '画像URLは正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは2048文字以内で入力してください。',

            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.array' => 'ジャンルの指定が正しくありません。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',

            'genres.*.integer' => 'ジャンルの指定が正しくありません。',
            'genres.*.distinct' => '同じジャンルが重複しています。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
