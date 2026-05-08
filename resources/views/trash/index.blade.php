<x-app-layout>
    <div x-data="trashBulkManager({
        csrf: '{{ csrf_token() }}',
        bulkRestoreUrl: '{{ route('trash.bulk.restore', [], false) }}',
        bulkForceUrl: '{{ route('trash.bulk.force', [], false) }}',
        items: @js(collect($trashedFiles->map(fn($file) => ['type' => 'file', 'id' => $file->id]))->merge($trashedFolders->map(fn($folder) => ['type' => 'folder', 'id' => $folder->id]))->values()),
    })" class="space-y-4">
        <h1 class="text-xl font-semibold flex items-center gap-2">
            <svg class="h-5 w-5 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6m4-6v6M6 7l1 12h10l1-12M9 7V4h6v3"/></svg>
            <span>Trash</span>
        </h1>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif

        <template x-if="selectedCount() > 0">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-indigo-700">
                    <span class="font-semibold" x-text="selectedCount()"></span> item selected
                    <span x-show="isBulkProcessing" class="ml-2 text-indigo-500" x-text="bulkActionLabel"></span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full md:w-auto">
                    <button type="button" class="rounded-lg border px-3 py-2 text-sm disabled:opacity-60 disabled:cursor-not-allowed" :disabled="isBulkProcessing" @click="bulkRestore()">Restore selected</button>
                    <button type="button" class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 disabled:opacity-60 disabled:cursor-not-allowed" :disabled="isBulkProcessing" @click="bulkForceDelete()">Delete permanent</button>
                    <button type="button" class="rounded-lg border px-3 py-2 text-sm disabled:opacity-60 disabled:cursor-not-allowed" :disabled="isBulkProcessing" @click="clearSelection()">Clear</button>
                </div>
            </div>
        </template>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">Deleted Files</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left w-10"><input type="checkbox" :checked="isAllFileSelected()" @change="toggleSelectAllType('file')"></th>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Deleted At</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($trashedFiles as $file)
                            <tr :class="isSelected('file', {{ $file->id }}) ? 'bg-indigo-50' : ''">
                                <td class="px-3 py-2"><input type="checkbox" :checked="isSelected('file', {{ $file->id }})" @click="toggleItemSelection('file', {{ $file->id }})"></td>
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
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">Trash files empty.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $trashedFiles->links() }}</div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Deleted Folders</h2>
                <label class="inline-flex items-center gap-2 text-xs text-slate-500">
                    <input type="checkbox" :checked="isAllFolderSelected()" @change="toggleSelectAllType('folder')" class="rounded border-slate-300">
                    Select all folders
                </label>
            </div>
            <div class="space-y-2">
                @forelse($trashedFolders as $folder)
                    <div class="border rounded-lg px-3 py-2 flex items-center justify-between" :class="isSelected('folder', {{ $folder->id }}) ? 'border-indigo-300 bg-indigo-50' : ''">
                        <div class="flex items-center gap-3 min-w-0">
                            <input type="checkbox" :checked="isSelected('folder', {{ $folder->id }})" @click="toggleItemSelection('folder', {{ $folder->id }})" class="rounded border-slate-300">
                            <div>
                                <div class="text-sm font-medium flex items-center gap-2">
                                    <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                                    <span>{{ $folder->name }}</span>
                                </div>
                                <div class="text-xs text-slate-500">Deleted: {{ $folder->deleted_at }}</div>
                            </div>
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

    <script>
        function trashBulkManager(config) {
            return {
                selectedItems: {},
                isBulkProcessing: false,
                bulkActionLabel: '',
                itemKey(type, id) {
                    return `${type}:${id}`;
                },
                isSelected(type, id) {
                    return !!this.selectedItems[this.itemKey(type, id)];
                },
                selectedCount() {
                    return Object.keys(this.selectedItems).length;
                },
                selectedFileIds() {
                    return Object.keys(this.selectedItems).filter((key) => key.startsWith('file:')).map((key) => Number(key.replace('file:', '')));
                },
                selectedFolderIds() {
                    return Object.keys(this.selectedItems).filter((key) => key.startsWith('folder:')).map((key) => Number(key.replace('folder:', '')));
                },
                isAllFileSelected() {
                    const fileItems = config.items.filter((item) => item.type === 'file');
                    return fileItems.length > 0 && fileItems.every((item) => this.isSelected('file', item.id));
                },
                isAllFolderSelected() {
                    const folderItems = config.items.filter((item) => item.type === 'folder');
                    return folderItems.length > 0 && folderItems.every((item) => this.isSelected('folder', item.id));
                },
                toggleSelectAllType(type) {
                    const targetItems = config.items.filter((item) => item.type === type);
                    const allSelected = targetItems.length > 0 && targetItems.every((item) => this.isSelected(type, item.id));
                    const next = { ...this.selectedItems };
                    targetItems.forEach((item) => {
                        const key = this.itemKey(type, item.id);
                        if (allSelected) {
                            delete next[key];
                        } else {
                            next[key] = true;
                        }
                    });
                    this.selectedItems = next;
                },
                toggleItemSelection(type, id) {
                    const key = this.itemKey(type, id);
                    const next = { ...this.selectedItems };
                    if (next[key]) {
                        delete next[key];
                    } else {
                        next[key] = true;
                    }
                    this.selectedItems = next;
                },
                clearSelection() {
                    if (this.isBulkProcessing) return;
                    this.selectedItems = {};
                },
                async runBulkAction(url, method) {
                    if (this.isBulkProcessing) return;
                    this.isBulkProcessing = true;
                    try {
                        const normalizedMethod = (method || 'POST').toUpperCase();
                        const useMethodSpoof = ['PATCH', 'PUT', 'DELETE'].includes(normalizedMethod);
                        const requestMethod = useMethodSpoof ? 'POST' : normalizedMethod;
                        const formBody = new URLSearchParams();
                        if (useMethodSpoof) {
                            formBody.append('_method', normalizedMethod);
                        }
                        this.selectedFileIds().forEach((id) => formBody.append('file_ids[]', String(id)));
                        this.selectedFolderIds().forEach((id) => formBody.append('folder_ids[]', String(id)));

                        const response = await fetch(url, {
                            method: requestMethod,
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formBody.toString(),
                        });

                        if (!response.ok) {
                            const payload = await response.json().catch(() => ({}));
                            window.alert(payload.message || 'Bulk action failed');
                            return;
                        }

                        window.location.reload();
                    } catch (error) {
                        window.alert(error?.message ? `Network error: ${error.message}` : 'Network error. Please try again.');
                    } finally {
                        this.isBulkProcessing = false;
                        this.bulkActionLabel = '';
                    }
                },
                async bulkRestore() {
                    this.bulkActionLabel = 'Restoring...';
                    await this.runBulkAction(config.bulkRestoreUrl, 'POST');
                },
                async bulkForceDelete() {
                    if (!window.confirm('Delete selected items permanently? This action cannot be undone.')) return;
                    this.bulkActionLabel = 'Deleting...';
                    await this.runBulkAction(config.bulkForceUrl, 'DELETE');
                },
            };
        }
    </script>
</x-app-layout>
