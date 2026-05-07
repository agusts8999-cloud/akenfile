<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateControlCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'theme' => ['required', 'in:light,dark,system'],
            'allowed_extensions' => ['required', 'string', 'max:1000'],
            'max_upload_size_mb' => ['required', 'integer', 'min:1', 'max:1024'],
            'storage_limit_gb' => ['required', 'integer', 'min:1', 'max:10240'],
            'control_center_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,null'],
            'smtp_from_email' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
