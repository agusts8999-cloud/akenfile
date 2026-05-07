<?php

namespace App\Http\Requests;

use App\Services\ControlCenterService;
use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $settings = app(ControlCenterService::class);
        $maxUploadMb = (int) $settings->getSetting('max_upload_size_mb', 10);
        $allowedExtensions = (string) $settings->getSetting('allowed_extensions', 'jpg,jpeg,png,gif,webp,pdf,txt,zip,doc,docx,xls,xlsx,ppt,pptx,csv,mp3,mp4,exe');
        $extensionList = collect(explode(',', strtolower($allowedExtensions)))
            ->map(fn (string $ext) => trim($ext))
            ->filter()
            ->values()
            ->all();
        if (count($extensionList) === 0) {
            $extensionList = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'zip', 'doc', 'docx'];
        }

        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:'.($maxUploadMb * 1024),
                'extensions:'.implode(',', $extensionList),
            ],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }
}
