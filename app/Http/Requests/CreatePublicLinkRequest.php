<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePublicLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shared_item' => ['required', 'regex:/^(file|folder)\:\d+$/'],
            'password' => ['nullable', 'string', 'min:6'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
