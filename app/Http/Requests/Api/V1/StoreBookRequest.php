<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
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
                'required',
                'integer',
                'distinct',
                'exists:genres,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者IDを指定してください。',
            'user_id.integer' => '登録者IDを正しく指定してください。',
            'user_id.exists' => '指定された登録者が存在しません。',

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
            'published_date.date' => '出版日を正しい日付形式で入力してください。',

            'description.string' => '説明を正しく入力してください。',

            'image_url.url' => '画像URLを正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは2048文字以内で入力してください。',

            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.array' => 'ジャンルを正しく指定してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.*.integer' => 'ジャンルを正しく指定してください。',
            'genres.*.distinct' => '同じジャンルが重複しています。',
            'genres.*.exists' => '存在しないジャンルが指定されています。',
        ];
    }
}
