<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareToUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shared_item' => ['required', 'regex:/^(file|folder)\:\d+$/'],
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['required', 'in:viewer,editor'],
        ];
    }
}
