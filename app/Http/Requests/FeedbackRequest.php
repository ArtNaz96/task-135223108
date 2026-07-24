<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:' . env('FEEDBACK_NAME_MAX_LENGTH', 255)],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'comment' => ['required', 'string', 'max:' . env('FEEDBACK_COMMENT_MAX_LENGTH', 2000)],
        ];
    }
}