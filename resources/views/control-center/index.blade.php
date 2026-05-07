<x-app-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold">Control Center</h1>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">System Info</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                <div class="border rounded-lg p-3"><div class="text-slate-500">App Version</div><div class="font-medium">{{ $systemInfo['app_version'] }}</div></div>
                <div class="border rounded-lg p-3"><div class="text-slate-500">PHP</div><div class="font-medium">{{ $systemInfo['php_version'] }}</div></div>
                <div class="border rounded-lg p-3"><div class="text-slate-500">Laravel</div><div class="font-medium">{{ $systemInfo['laravel_version'] }}</div></div>
                <div class="border rounded-lg p-3"><div class="text-slate-500">Database</div><div class="font-medium">{{ $systemInfo['db_connection'] }}</div></div>
                <div class="border rounded-lg p-3 md:col-span-2 lg:col-span-4"><div class="text-slate-500">Storage</div><div class="font-medium">{{ number_format(($systemInfo['storage_used_bytes'] ?? 0) / 1024 / 1024, 2) }} MB / {{ number_format($systemInfo['storage_limit_gb'] ?? 10, 2) }} GB</div></div>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">System Settings</h2>
            <form method="POST" action="{{ route('control-center.settings.update') }}" class="grid md:grid-cols-3 gap-3">
                @csrf
                <select name="theme" class="rounded-lg border-slate-300 text-sm">
                    <option value="system" @selected(($settings['theme'] ?? 'system') === 'system')>System</option>
                    <option value="light" @selected(($settings['theme'] ?? '') === 'light')>Light</option>
                    <option value="dark" @selected(($settings['theme'] ?? '') === 'dark')>Dark</option>
                </select>
                <input name="allowed_extensions" value="{{ $settings['allowed_extensions'] ?? 'jpg,jpeg,png,gif,webp,pdf,txt,zip,doc,docx,xls,xlsx,ppt,pptx,csv,mp3,mp4,exe' }}" class="rounded-lg border-slate-300 text-sm" placeholder="allowed extensions comma separated">
                <input name="max_upload_size_mb" value="{{ $settings['max_upload_size_mb'] ?? '10' }}" type="number" min="1" class="rounded-lg border-slate-300 text-sm" placeholder="max upload MB">
                <input name="storage_limit_gb" value="{{ $settings['storage_limit_gb'] ?? '10' }}" type="number" min="1" class="rounded-lg border-slate-300 text-sm" placeholder="storage space GB per user">
                <div class="md:col-span-3 border-t pt-3 mt-1">
                    <h3 class="font-medium text-sm mb-2">Control Center Password</h3>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div x-data="{show:false}" style="position: relative;">
                            <input :type="show ? 'text' : 'password'" name="control_center_password" placeholder="New password" class="w-full rounded-lg border-slate-300 text-sm pr-11">
                            <button type="button" @click="show=!show" class="z-10 text-slate-500 hover:text-slate-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                                <span class="sr-only">Toggle password visibility</span>
                                <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                                    <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                                </svg>
                                <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                                </svg>
                            </button>
                        </div>
                        <div x-data="{show:false}" style="position: relative;">
                            <input :type="show ? 'text' : 'password'" name="control_center_password_confirmation" placeholder="Confirm password" class="w-full rounded-lg border-slate-300 text-sm pr-11">
                            <button type="button" @click="show=!show" class="z-10 text-slate-500 hover:text-slate-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                                <span class="sr-only">Toggle password visibility</span>
                                <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                                    <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                                </svg>
                                <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3 border-t pt-3 mt-1">
                    <h3 class="font-medium text-sm mb-2">SMTP Email Settings (Send Share Link)</h3>
                    <div class="grid md:grid-cols-3 gap-3">
                        <input name="smtp_host" value="{{ $settings['smtp_host'] ?? '' }}" class="rounded-lg border-slate-300 text-sm" placeholder="SMTP host">
                        <input name="smtp_port" value="{{ $settings['smtp_port'] ?? '587' }}" type="number" class="rounded-lg border-slate-300 text-sm" placeholder="SMTP port">
                        <select name="smtp_encryption" class="rounded-lg border-slate-300 text-sm">
                            <option value="tls" @selected(($settings['smtp_encryption'] ?? 'tls') === 'tls')>TLS</option>
                            <option value="ssl" @selected(($settings['smtp_encryption'] ?? '') === 'ssl')>SSL</option>
                            <option value="null" @selected(($settings['smtp_encryption'] ?? '') === 'null')>None</option>
                        </select>
                        <input name="smtp_username" value="{{ $settings['smtp_username'] ?? '' }}" class="rounded-lg border-slate-300 text-sm" placeholder="SMTP username">
                        <div x-data="{show:false}" style="position: relative;">
                            <input :type="show ? 'text' : 'password'" name="smtp_password" placeholder="SMTP password" class="w-full rounded-lg border-slate-300 text-sm pr-11">
                            <button type="button" @click="show=!show" class="z-10 text-slate-500 hover:text-slate-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                                <span class="sr-only">Toggle password visibility</span>
                                <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                                    <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                                </svg>
                                <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                                </svg>
                            </button>
                        </div>
                        <div x-data="{show:false}" style="position: relative;">
                            <input :type="show ? 'text' : 'password'" name="smtp_password_confirmation" placeholder="Confirm SMTP password" class="w-full rounded-lg border-slate-300 text-sm pr-11">
                            <button type="button" @click="show=!show" class="z-10 text-slate-500 hover:text-slate-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                                <span class="sr-only">Toggle password visibility</span>
                                <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                                    <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                                </svg>
                                <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                                </svg>
                            </button>
                        </div>
                        <input name="smtp_from_email" value="{{ $settings['smtp_from_email'] ?? '' }}" type="email" class="rounded-lg border-slate-300 text-sm" placeholder="From email">
                        <input name="smtp_from_name" value="{{ $settings['smtp_from_name'] ?? 'AkenFile' }}" class="rounded-lg border-slate-300 text-sm" placeholder="From name">
                    </div>
                </div>

                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white md:col-span-3">Save Settings</button>
            </form>
        </div>
    </div>
</x-app-layout>
