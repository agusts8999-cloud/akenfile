<x-app-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold">Dashboard</h1>

        <div class="rounded-xl border bg-white p-4">
            <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
                <h2 class="font-semibold">Grafik Aktivitas Harian ({{ $periodDays }} Hari)</h2>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard', ['period' => 7]) }}" class="rounded-lg border px-2.5 py-1 text-xs {{ $periodDays === 7 ? 'bg-indigo-600 text-white border-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">7 Hari</a>
                    <a href="{{ route('dashboard', ['period' => 30]) }}" class="rounded-lg border px-2.5 py-1 text-xs {{ $periodDays === 30 ? 'bg-indigo-600 text-white border-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">30 Hari</a>
                    <span class="text-xs text-slate-500">File + Folder + Trash + Share</span>
                </div>
            </div>
            @php
                $chartItems = $activityChart ?? [];
                $totalActivity = max(1, array_sum(array_map(fn ($row) => (int) ($row['count'] ?? 0), $chartItems)));
                $pieColors = ['#6366f1', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#84cc16', '#f97316', '#14b8a6'];
                $centerX = 110;
                $centerY = 110;
                $radius = 78;
                $circumference = 2 * pi() * $radius;
                $accumulated = 0;
                $slices = [];

                foreach ($chartItems as $index => $row) {
                    $count = (int) ($row['count'] ?? 0);
                    if ($count <= 0) {
                        continue;
                    }
                    $fraction = $count / $totalActivity;
                    $sliceLength = $circumference * $fraction;
                    $offset = -$accumulated;
                    $accumulated += $sliceLength;

                    $slices[] = [
                        'label' => $row['label'] ?? '-',
                        'date' => $row['date'] ?? '-',
                        'count' => $count,
                        'percent' => round($fraction * 100, 1),
                        'color' => $pieColors[$index % count($pieColors)],
                        'length' => round($sliceLength, 2),
                        'offset' => round($offset, 2),
                    ];
                }
            @endphp

            <div class="grid gap-4 lg:grid-cols-2 items-center">
                <div class="flex items-center justify-center">
                    <svg viewBox="0 0 220 220" class="h-64 w-64">
                        <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $radius }}" fill="none" stroke="#e2e8f0" stroke-width="28" />
                        @if(count($slices) > 0)
                            @foreach($slices as $slice)
                                <circle
                                    cx="{{ $centerX }}"
                                    cy="{{ $centerY }}"
                                    r="{{ $radius }}"
                                    fill="none"
                                    stroke="{{ $slice['color'] }}"
                                    stroke-width="28"
                                    stroke-dasharray="{{ $slice['length'] }} {{ round($circumference - $slice['length'], 2) }}"
                                    stroke-dashoffset="{{ $slice['offset'] }}"
                                    transform="rotate(-90 {{ $centerX }} {{ $centerY }})"
                                >
                                    <title>{{ $slice['date'] }}: {{ $slice['count'] }} aktivitas ({{ $slice['percent'] }}%)</title>
                                </circle>
                            @endforeach
                        @endif
                        <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="48" fill="white" />
                        <text x="{{ $centerX }}" y="{{ $centerY - 4 }}" text-anchor="middle" class="fill-slate-500" style="font-size: 11px;">Total</text>
                        <text x="{{ $centerX }}" y="{{ $centerY + 16 }}" text-anchor="middle" class="fill-slate-800" style="font-size: 18px; font-weight: 700;">{{ number_format(array_sum(array_map(fn ($row) => (int) ($row['count'] ?? 0), $chartItems))) }}</text>
                    </svg>
                </div>

                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    @forelse($slices as $slice)
                        <div class="flex items-center justify-between rounded-lg border px-3 py-2 text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="h-3 w-3 rounded-sm shrink-0" style="background-color: {{ $slice['color'] }};"></span>
                                <span class="text-slate-700 truncate">{{ $slice['date'] }}</span>
                            </div>
                            <div class="text-slate-600 whitespace-nowrap">{{ $slice['count'] }} ({{ $slice['percent'] }}%)</div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Belum ada data aktivitas untuk periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-3 text-sm" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Total Files</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($stats['total_files'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Total Folders</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($stats['total_folders'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Storage Used</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format(($stats['storage_used_bytes'] ?? 0) / 1024 / 1024, 2) }} MB</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Items in Trash</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($stats['trash_items'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Active Shares</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($stats['active_shares'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Activity {{ $periodDays }} Hari</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($activityWeekly['total'] ?? 0) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total activity dalam {{ $periodDays }} hari terakhir</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Rata-rata Harian</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($activityWeekly['average'] ?? 0, 1) }}</div>
                <div class="text-xs text-slate-500 mt-1">Aktivitas per hari ({{ $periodDays }} hari)</div>
            </div>
            <div class="rounded-xl border bg-white p-4">
                <div class="text-slate-500">Puncak Aktivitas</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ number_format($activityWeekly['peak'] ?? 0) }}</div>
                <div class="text-xs text-slate-500 mt-1">Hari tertinggi: {{ $activityWeekly['peak_label'] ?? '-' }}</div>
            </div>
        </div>

        <div x-data="{ showRecentActivity: false }" class="rounded-xl border bg-white p-4">
        @php
            $activityMeta = [
                'file' => ['label' => 'File', 'badge' => 'bg-sky-100 text-sky-700', 'icon' => 'file'],
                'folder' => ['label' => 'Folder', 'badge' => 'bg-amber-100 text-amber-700', 'icon' => 'folder'],
                'trash-file' => ['label' => 'Trash', 'badge' => 'bg-rose-100 text-rose-700', 'icon' => 'trash'],
                'share' => ['label' => 'Share', 'badge' => 'bg-emerald-100 text-emerald-700', 'icon' => 'share'],
            ];
        @endphp

            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Recent Activity</h2>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-sm hover:bg-slate-50" @click="showRecentActivity = !showRecentActivity">
                        <span x-show="!showRecentActivity">Unhide</span>
                        <span x-show="showRecentActivity">Hide</span>
                    </button>
                    <a href="{{ route('files.index') }}" class="rounded-lg border px-3 py-1.5 text-sm hover:bg-slate-50">Go to MyFiles</a>
                </div>
            </div>

            <div x-show="showRecentActivity" x-transition class="space-y-2">
                @forelse($recentActivity as $activity)
                    @php
                    $meta = $activityMeta[$activity['type']] ?? [
                        'label' => ucfirst(str_replace('-', ' ', $activity['type'])),
                        'badge' => 'bg-slate-100 text-slate-700',
                        'icon' => 'file',
                    ];
                @endphp
                    <div class="rounded-lg border px-3 py-2 flex items-start justify-between gap-3">
                        <div class="min-w-0 flex items-start gap-2">
                            <div class="mt-0.5 text-slate-400">
                                @if($meta['icon'] === 'folder')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                                @elseif($meta['icon'] === 'trash')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6m4-6v6M6 7l1 12h10l1-12M9 7V4h6v3"/></svg>
                                @elseif($meta['icon'] === 'share')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 8a3 3 0 1 0-2.83-4H13a3 3 0 0 0 .17 1L8.7 8.1A3 3 0 0 0 6 7a3 3 0 1 0 2.7 4.5l4.48 3.1a3 3 0 1 0 .85-1.22L9.55 10.3A3 3 0 0 0 9 9c0-.21.02-.42.06-.62l4.46-3.06A3 3 0 0 0 16 8Z"/></svg>
                                @else
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-slate-800 truncate">{{ $activity['label'] }}</div>
                                <div class="mt-1 text-xs text-slate-500 flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 font-medium {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                                    <span>{{ $activity['action'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 whitespace-nowrap">
                            {{ optional($activity['timestamp'])->format('M d, Y H:i') }}
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">No recent activity yet.</div>
                @endforelse
            </div>
            <div x-show="!showRecentActivity" class="rounded-lg border border-dashed px-3 py-4 text-sm text-slate-500">
                Recent Activity disembunyikan. Klik <span class="font-medium">Unhide</span> untuk menampilkan.
            </div>
        </div>
    </div>
</x-app-layout>
