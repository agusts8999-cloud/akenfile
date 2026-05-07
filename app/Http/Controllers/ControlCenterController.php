<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateControlCenterRequest;
use App\Models\File;
use App\Services\ControlCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ControlCenterController extends Controller
{
    public function __construct(private readonly ControlCenterService $controlCenterService)
    {
    }

    public function index(): View
    {
        $settings = $this->controlCenterService->settingsMap();
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection' => DB::getDriverName(),
            'storage_used_bytes' => (int) File::query()->sum('size'),
            'storage_limit_gb' => (int) ($settings['storage_limit_gb'] ?? 10),
            'app_version' => file_exists(public_path('build/manifest.json'))
                ? 'v'.date('Y.m.d-His', filemtime(public_path('build/manifest.json')))
                : 'v1.0.0',
        ];

        return view('control-center.index', [
            'settings' => $settings,
            'systemInfo' => $systemInfo,
        ]);
    }

    public function updateSettings(UpdateControlCenterRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $this->controlCenterService->setSetting('theme', (string) $request->input('theme'));
        $this->controlCenterService->setSetting('allowed_extensions', (string) $request->input('allowed_extensions'));
        $this->controlCenterService->setSetting('max_upload_size_mb', (string) $request->input('max_upload_size_mb'), 'integer');
        $this->controlCenterService->setSetting('storage_limit_gb', (string) $request->input('storage_limit_gb'), 'integer');
        $this->controlCenterService->setSetting('smtp_host', (string) $request->input('smtp_host'));
        $this->controlCenterService->setSetting('smtp_port', (string) $request->input('smtp_port'), 'integer');
        $this->controlCenterService->setSetting('smtp_username', (string) $request->input('smtp_username'));
        $this->controlCenterService->setSetting('smtp_encryption', (string) $request->input('smtp_encryption', 'tls'));
        $this->controlCenterService->setSetting('smtp_from_email', (string) $request->input('smtp_from_email'));
        $this->controlCenterService->setSetting('smtp_from_name', (string) $request->input('smtp_from_name'));

        if ($request->filled('control_center_password')) {
            $this->controlCenterService->setSetting(
                'control_center_password',
                Hash::make((string) $request->input('control_center_password'))
            );
        }

        if ($request->filled('smtp_password')) {
            $this->controlCenterService->setSetting(
                'smtp_password',
                encrypt((string) $request->input('smtp_password')),
                'encrypted'
            );
        }

        return back()->with('status', 'Settings updated.');
    }
}
