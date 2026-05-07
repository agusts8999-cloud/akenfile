<?php

namespace App\Services;

use App\Models\PluginRequest;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Collection;

class ControlCenterService
{
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return SystemSetting::query()->where('key', $key)->value('value') ?? $default;
    }

    public function setSetting(string $key, string $value, string $type = 'string'): SystemSetting
    {
        return SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    public function settingsMap(): array
    {
        return SystemSetting::query()->get()->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $setting->value])->all();
    }

    public function smtpConfig(): array
    {
        $settings = $this->settingsMap();

        $password = null;
        if (! empty($settings['smtp_password'])) {
            try {
                $password = decrypt($settings['smtp_password']);
            } catch (\Throwable) {
                $password = null;
            }
        }

        return [
            'host' => $settings['smtp_host'] ?? null,
            'port' => (int) ($settings['smtp_port'] ?? 587),
            'username' => $settings['smtp_username'] ?? null,
            'password' => $password,
            'encryption' => ($settings['smtp_encryption'] ?? 'tls') === 'null' ? null : ($settings['smtp_encryption'] ?? 'tls'),
            'from_email' => $settings['smtp_from_email'] ?? null,
            'from_name' => $settings['smtp_from_name'] ?? 'AkenFile',
        ];
    }

    public function submitPluginRequest(int $userId, string $name, ?string $description): PluginRequest
    {
        return PluginRequest::query()->create([
            'name' => $name,
            'description' => $description,
            'requested_by' => $userId,
            'status' => 'pending',
        ]);
    }

    public function updatePluginRequestStatus(PluginRequest $request, string $status, ?string $adminNote = null): PluginRequest
    {
        $request->update([
            'status' => $status,
            'admin_note' => $adminNote,
        ]);

        return $request->refresh();
    }

    public function pluginRequests(): Collection
    {
        return PluginRequest::query()->with('requester')->latest()->get();
    }
}
