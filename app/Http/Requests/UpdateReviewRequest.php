<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    /**
     * 認可はReviewPolicyで行う
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * レビュー更新Validation
     */
    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'comment' => [
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
        return [
            'rating.required' => '評価を選択してください。',
            'rating.integer' => '評価の値が正しくありません。',
            'rating.between' => '評価は1〜5の範囲で選択してください。',

            'comment.string' => 'コメントを正しく入力してください。',
        ];
    }
}
