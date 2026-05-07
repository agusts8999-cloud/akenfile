<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AkenFile') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-800">
    @php
        try {
            $storageLimitGb = (int) (\App\Models\SystemSetting::query()->where('key', 'storage_limit_gb')->value('value') ?? 10);
        } catch (\Throwable) {
            $storageLimitGb = 10;
        }
        $storageLimit = max(1, $storageLimitGb) * 1024 * 1024 * 1024;
        if (! isset($storageUsedBytes) && auth()->check()) {
            $storageUsedBytes = (int) \App\Models\File::query()
                ->when(! auth()->user()->isAdmin(), fn ($query) => $query->where('user_id', auth()->id()))
                ->sum('size');
        }
        $storageUsed = $storageUsedBytes ?? 0;
        $storagePctRaw = min(100, ($storageUsed / max($storageLimit, 1)) * 100);
        $storagePctBar = $storagePctRaw > 0 ? max($storagePctRaw, 1) : 0;
        $storagePctLabel = number_format($storagePctRaw, $storagePctRaw < 10 ? 1 : 0);
        $formatBytes = function (int $bytes): string {
            if ($bytes >= 1024 * 1024 * 1024) {
                return number_format($bytes / (1024 * 1024 * 1024), 2).' GB';
            }
            if ($bytes >= 1024 * 1024) {
                return number_format($bytes / (1024 * 1024), 2).' MB';
            }
            return number_format($bytes / 1024, 2).' KB';
        };
        $manifestPath = public_path('build/manifest.json');
        $appVersion = file_exists($manifestPath) ? 'v'.date('Y.m.d-His', filemtime($manifestPath)) : 'v1.0.0';
    @endphp
    <div class="min-h-screen bg-slate-100">
        <aside class="hidden lg:flex lg:flex-col fixed left-0 top-0 h-screen w-64 bg-slate-950 text-slate-100 px-4 py-5 border-r border-slate-800 z-30">
            <div class="mb-8 flex items-center gap-2">
                <img src="{{ asset('assets/akenfile-logo.png') }}" alt="AkenFile Logo" class="h-8 w-8 rounded-lg object-cover">
                <div class="text-lg font-semibold tracking-wide">AkenFile</div>
            </div>

            <nav class="space-y-1.5 text-sm">
                <a class="flex items-center gap-2 px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-200' }}" href="{{ route('dashboard') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5 12 3l9 7.5V21H3V10.5Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 21v-6h6v6"/></svg>
                    <span>Dashboard</span>
                </a>
                <a class="flex items-center gap-2 px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('files.*') ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-200' }}" href="{{ route('files.index') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                    <span>My Files</span>
                </a>
                <a class="flex items-center gap-2 px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('shared.*') ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-200' }}" href="{{ route('shared.index') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 8a3 3 0 1 0-2.83-4H13a3 3 0 0 0 .17 1L8.7 8.1A3 3 0 0 0 6 7a3 3 0 1 0 2.7 4.5l4.48 3.1a3 3 0 1 0 .85-1.22L9.55 10.3A3 3 0 0 0 9 9c0-.21.02-.42.06-.62l4.46-3.06A3 3 0 0 0 16 8Z"/></svg>
                    <span>Shared</span>
                </a>
                <a class="flex items-center gap-2 px-3 py-2.5 rounded-lg font-medium {{ request()->routeIs('trash.*') ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-200' }}" href="{{ route('trash.index') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6m4-6v6M6 7l1 12h10l1-12M9 7V4h6v3"/></svg>
                    <span>Trash</span>
                </a>
            </nav>

            <div class="mt-6 border-t border-slate-800 pt-4">
                <div class="mb-2 px-3 text-[11px] uppercase tracking-wider text-slate-500">Management</div>
                @if(auth()->user()?->isAdmin())
                    <a class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-200' }}" href="{{ route('users.index') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0ZM4 20a8 8 0 0 1 16 0"/></svg>
                        <span>Users</span>
                    </a>
                    <a class="mt-1 flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('control-center.*') ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-200' }}" href="{{ route('control-center.index') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19.4 15 .9 1.55-1.8 3.12-1.8-.35a7.6 7.6 0 0 1-1.2.7l-.55 1.75H9.05l-.55-1.75a7.6 7.6 0 0 1-1.2-.7l-1.8.35-1.8-3.12.9-1.55a8.2 8.2 0 0 1 0-1.4l-.9-1.55 1.8-3.12 1.8.35c.38-.26.78-.49 1.2-.69l.55-1.76h3.9l.55 1.76c.42.2.82.43 1.2.69l1.8-.35 1.8 3.12-.9 1.55c.07.46.07.94 0 1.4Z"/></svg>
                        <span>Control Center</span>
                    </a>
                @endif
            </div>

            <div class="mt-6 mt-auto rounded-xl bg-slate-900/80 border border-slate-700 p-4">
                <div class="text-sm font-medium text-slate-200">Storage</div>
                <div class="mt-1 text-xs text-slate-400">{{ $formatBytes($storageUsed) }} / {{ number_format($storageLimitGb, 2) }} GB used</div>
                <div class="mt-3 h-2 w-full rounded-full bg-slate-700">
                    <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $storagePctBar }}%"></div>
                </div>
                <div class="mt-2 text-right text-xs text-slate-400">{{ $storagePctLabel }}%</div>
                <button type="button" class="mt-4 w-full rounded-lg bg-indigo-600 px-3 py-2.5 text-xs font-semibold text-white hover:bg-indigo-500">Upgrade Storage</button>
                <div class="mt-3 text-center text-[10px] text-slate-500">{{ $appVersion }}</div>
            </div>
        </aside>

        <div class="min-h-screen lg:ml-64">
            <header class="bg-white shadow-sm border-b">
                <div class="px-4 py-3 md:px-6 flex items-center justify-between gap-4">
                    <form method="GET" action="{{ route('files.index') }}" class="w-full max-w-lg">
                        <input name="search" value="{{ request('search') }}" type="text" placeholder="Search files and folders..." class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </form>
                    <div class="flex items-center gap-3">
                        <button type="button" class="rounded-full border h-9 w-9 text-slate-500">•</button>
                        <div class="text-right hidden md:block">
                            <div class="text-sm font-semibold">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-500">{{ auth()->user()->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="p-4 md:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
