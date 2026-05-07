<x-app-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold flex items-center gap-2">
            <svg class="h-5 w-5 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6m4-6v6M6 7l1 12h10l1-12M9 7V4h6v3"/></svg>
            <span>Trash</span>
        </h1>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">Deleted Files</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Deleted At</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($trashedFiles as $file)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                                        <span>{{ $file->name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">{{ $file->deleted_at }}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('trash.files.restore', $file->id) }}">
                                            @csrf
                                            <button class="text-xs text-indigo-600">Restore</button>
                                        </form>
                                        <form method="POST" action="{{ route('trash.files.force', $file->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-red-600">Delete Permanent</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-8 text-center text-slate-500">Trash files empty.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $trashedFiles->links() }}</div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">Deleted Folders</h2>
            <div class="space-y-2">
                @forelse($trashedFolders as $folder)
                    <div class="border rounded-lg px-3 py-2 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium flex items-center gap-2">
                                <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                                <span>{{ $folder->name }}</span>
                            </div>
                            <div class="text-xs text-slate-500">Deleted: {{ $folder->deleted_at }}</div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('trash.folders.restore', $folder->id) }}">
                                @csrf
                                <button class="text-xs text-indigo-600">Restore</button>
                            </form>
                            <form method="POST" action="{{ route('trash.folders.force', $folder->id) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-red-600">Delete Permanent</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Trash folders empty.</div>
                @endforelse
            </div>
            <div class="mt-3">{{ $trashedFolders->links() }}</div>
        </div>
    </div>
</x-app-layout>
