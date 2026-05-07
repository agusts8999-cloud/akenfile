<x-app-layout>
    <div x-data="fileManager({
        uploadUrl: '{{ route('files.upload') }}',
        fileRenameUrlTemplate: '{{ route('files.rename', ['file' => '__ID__']) }}',
        fileMoveUrlTemplate: '{{ route('files.move', ['file' => '__ID__']) }}',
        fileDeleteUrlTemplate: '{{ route('files.destroy', ['file' => '__ID__']) }}',
        folderRenameUrlTemplate: '{{ route('folders.rename', ['folder' => '__ID__']) }}',
        folderDeleteUrlTemplate: '{{ route('folders.destroy', ['folder' => '__ID__']) }}',
        folderId: '{{ $currentFolder?->id }}',
        csrf: '{{ csrf_token() }}'
    })" class="space-y-4">
        <div class="rounded-xl border bg-white px-4 py-3 flex items-center justify-between">
            <div class="text-sm text-slate-500 flex items-center gap-1">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5 12 3l9 7.5V21H3V10.5Z"/></svg>
                <a href="{{ route('files.index') }}" class="hover:text-indigo-600">Root</a>
                @foreach ($breadcrumbs as $crumb)
                    <span class="mx-1">/</span>
                    <a href="{{ route('files.index', ['folder_id' => $crumb->id]) }}" class="hover:text-indigo-600">{{ $crumb->name }}</a>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <button @click="$refs.fileInput.click()" type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Upload</button>
                <button @click="$refs.folderInput.click()" type="button" class="rounded-lg border px-4 py-2 text-sm">Upload folder</button>
                <button @click="createFolder()" type="button" class="rounded-lg border px-4 py-2 text-sm">New folder</button>
                <button type="button" class="rounded-lg border px-4 py-2 text-sm">Create</button>
            </div>
        </div>

        <input x-ref="fileInput" type="file" class="hidden" multiple @change="queueFiles($event.target.files)">
        <input x-ref="folderInput" type="file" class="hidden" multiple webkitdirectory directory @change="queueFiles($event.target.files)">

        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center" @dragover.prevent @drop.prevent="queueFiles($event.dataTransfer.files)">
            <div class="text-slate-600">Drag & drop files or folders here</div>
            <div class="text-xs text-slate-400 mt-1">You can upload multiple files or entire folders</div>
            <div class="mt-4 flex justify-center gap-2">
                <button @click="$refs.fileInput.click()" type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Select files</button>
                <button @click="$refs.folderInput.click()" type="button" class="rounded-lg border px-4 py-2 text-sm">Select folder</button>
            </div>
        </div>

        <template x-if="queue.length > 0">
            <div class="rounded-xl border bg-white divide-y">
                <template x-for="item in queue" :key="item.id">
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="truncate pr-4">
                                <span class="font-medium" x-text="item.name"></span>
                                <span class="ml-2 text-slate-400" x-text="formatSize(item.size)"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs uppercase text-slate-500" x-text="item.status"></span>
                                <button type="button" class="rounded border px-2 py-1 text-xs" @click="togglePause(item)" x-show="item.status === 'uploading' || item.status === 'paused'">
                                    <span x-text="item.status === 'paused' ? 'Resume' : 'Pause'"></span>
                                </button>
                                <button type="button" class="rounded border px-2 py-1 text-xs text-red-600" @click="cancelUpload(item)">Cancel</button>
                                <button type="button" class="rounded border px-2 py-1 text-xs text-indigo-600" x-show="item.status === 'failed'" @click="retryUpload(item)">Retry</button>
                            </div>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-indigo-600 transition-all" :style="`width: ${item.progress}%`"></div>
                        </div>
                        <div class="mt-1 text-xs text-slate-500 flex justify-between">
                            <span x-text="`${item.progress}%`"></span>
                            <span class="text-red-500" x-text="item.errorMessage"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <div class="rounded-xl border bg-white p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold flex items-center gap-2">
                    <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                    <span>Folders</span>
                </h3>
                <div class="flex gap-2">
                    <button class="rounded border px-2 py-1 text-xs">Grid</button>
                    <button class="rounded border px-2 py-1 text-xs">List</button>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                @forelse ($folders as $folder)
                    <div class="rounded-lg border p-3">
                        <div class="flex items-start justify-between">
                            <a href="{{ route('files.index', ['folder_id' => $folder->id]) }}" class="font-medium text-sm hover:text-indigo-600 truncate flex items-center gap-2">
                                <svg class="h-4 w-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                                <span class="truncate">{{ $folder->name }}</span>
                            </a>
                            <button type="button" class="text-slate-400" @click="folderAction({{ $folder->id }}, '{{ $folder->name }}')">⋮</button>
                        </div>
                        <div class="text-xs text-slate-400 mt-1">{{ $folder->children_count + $folder->files_count }} items</div>
                    </div>
                @empty
                    <div class="col-span-full text-sm text-slate-500 py-6 text-center">No folders found.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h3 class="font-semibold flex items-center gap-2">
                    <svg class="h-4 w-4 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                    <span>Files</span>
                </h3>
                <div class="flex gap-2">
                    <button type="button" @click="viewMode='table'" class="rounded border px-2 py-1 text-xs">Table</button>
                    <button type="button" @click="viewMode='grid'" class="rounded border px-2 py-1 text-xs">Grid</button>
                </div>
            </div>

            <div x-show="viewMode === 'table'" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2 text-left w-10"><input type="checkbox"></th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Size</th>
                            <th class="px-4 py-2 text-left">Last Modified</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($files as $file)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><input type="checkbox"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-sky-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                                        <span class="truncate">{{ $file->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ number_format($file->size / 1024 / 1024, 2) }} MB</td>
                                <td class="px-4 py-3">{{ $file->updated_at->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end items-center gap-2 text-xs">
                                        <a class="rounded border px-2 py-1 hover:text-indigo-600" href="{{ route('files.download', $file) }}">Download</a>
                                        <button type="button" class="rounded border px-2 py-1" @click="renameFile({{ $file->id }}, '{{ $file->name }}')">Rename</button>
                                        <button type="button" class="rounded border px-2 py-1" @click="moveFile({{ $file->id }})">Move</button>
                                        <button type="button" class="rounded border border-red-200 px-2 py-1 text-red-600" @click="deleteFile({{ $file->id }})">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-slate-500">No files in this location.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div x-show="viewMode === 'grid'" class="p-4 grid grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach ($files as $file)
                    <div class="rounded-lg border p-3">
                        <div class="font-medium text-sm truncate flex items-center gap-2">
                            <svg class="h-4 w-4 text-sky-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                            <span class="truncate">{{ $file->name }}</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1">{{ number_format($file->size / 1024 / 1024, 2) }} MB</div>
                        <div class="mt-3 flex gap-2 text-xs">
                            <a href="{{ route('files.download', $file) }}" class="rounded border px-2 py-1">Download</a>
                            <button type="button" class="rounded border border-red-200 px-2 py-1 text-red-600" @click="deleteFile({{ $file->id }})">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-4 py-3 border-t text-sm text-slate-500 flex items-center justify-between">
                <span>Showing {{ $files->count() }} items</span>
                <span>{{ $files->links() }}</span>
            </div>
        </div>
    </div>

    <script>
        function fileManager(config) {
            return {
                viewMode: 'table',
                queue: [],
                activeUploads: 0,
                maxParallel: 3,
                queueFiles(fileList) {
                    Array.from(fileList).forEach((file) => {
                        this.queue.push({
                            id: crypto.randomUUID(),
                            file,
                            name: file.webkitRelativePath || file.name,
                            size: file.size || 0,
                            progress: 0,
                            status: 'queued',
                            errorMessage: '',
                            xhr: null,
                        });
                    });
                    this.processQueue();
                },
                processQueue() {
                    while (this.activeUploads < this.maxParallel) {
                        const next = this.queue.find((item) => item.status === 'queued');
                        if (!next) break;
                        this.startUpload(next);
                    }
                },
                startUpload(item) {
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData();
                    formData.append('_token', config.csrf);
                    formData.append('file', item.file);
                    if (config.folderId) formData.append('folder_id', config.folderId);

                    item.status = 'uploading';
                    item.errorMessage = '';
                    item.xhr = xhr;
                    this.activeUploads += 1;

                    xhr.upload.onprogress = (event) => {
                        if (event.lengthComputable) {
                            item.progress = Math.min(95, Math.round((event.loaded / event.total) * 95));
                        }
                    };

                    xhr.onload = () => {
                        this.activeUploads = Math.max(0, this.activeUploads - 1);
                        if (xhr.status >= 200 && xhr.status < 300) {
                            item.progress = 100;
                            item.status = 'success';
                            setTimeout(() => window.location.reload(), 350);
                        } else {
                            item.status = 'failed';
                            let errorMessage = 'Upload failed';
                            try {
                                const payload = JSON.parse(xhr.responseText || '{}');
                                if (payload?.errors?.file?.[0]) {
                                    errorMessage = payload.errors.file[0];
                                } else if (payload?.message) {
                                    errorMessage = payload.message;
                                }
                            } catch (e) {
                                errorMessage = `Upload failed (${xhr.status})`;
                            }
                            item.errorMessage = errorMessage;
                        }
                        this.processQueue();
                    };

                    xhr.onerror = () => {
                        this.activeUploads = Math.max(0, this.activeUploads - 1);
                        item.status = 'failed';
                        item.errorMessage = 'Network error';
                        this.processQueue();
                    };

                    xhr.onabort = () => {
                        this.activeUploads = Math.max(0, this.activeUploads - 1);
                        if (item.status !== 'paused') item.status = 'cancelled';
                        this.processQueue();
                    };

                    xhr.open('POST', config.uploadUrl, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.send(formData);
                },
                togglePause(item) {
                    if (item.status === 'uploading' && item.xhr) {
                        item.status = 'paused';
                        item.xhr.abort();
                    } else if (item.status === 'paused') {
                        item.status = 'queued';
                        this.processQueue();
                    }
                },
                cancelUpload(item) {
                    if (item.xhr && item.status === 'uploading') {
                        item.xhr.abort();
                    }
                    item.status = 'cancelled';
                },
                retryUpload(item) {
                    item.status = 'queued';
                    item.progress = 0;
                    item.errorMessage = '';
                    this.processQueue();
                },
                formatSize(bytes) {
                    if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
                    if (bytes >= 1024) return `${(bytes / 1024).toFixed(2)} KB`;
                    return `${bytes} B`;
                },
                createFolder() {
                    const name = window.prompt('Folder name');
                    if (!name) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('folders.store') }}';
                    form.innerHTML = `<input type="hidden" name="_token" value="${config.csrf}">
                        <input type="hidden" name="name" value="${name}">
                        <input type="hidden" name="parent_id" value="${config.folderId || ''}">`;
                    document.body.appendChild(form);
                    form.submit();
                },
                async renameFile(fileId, oldName) {
                    const name = window.prompt('Rename file', oldName);
                    if (!name || name === oldName) return;
                    await fetch(config.fileRenameUrlTemplate.replace('__ID__', fileId), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ name }),
                    });
                    window.location.reload();
                },
                async moveFile(fileId) {
                    const folderId = window.prompt('Move to folder ID (empty = root)', '');
                    await fetch(config.fileMoveUrlTemplate.replace('__ID__', fileId), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ folder_id: folderId || null, name: 'keep' }),
                    });
                    window.location.reload();
                },
                async deleteFile(fileId) {
                    if (!window.confirm('Delete this file?')) return;
                    await fetch(config.fileDeleteUrlTemplate.replace('__ID__', fileId), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                    });
                    window.location.reload();
                },
                async folderAction(folderId, folderName) {
                    const action = window.prompt(`Folder "${folderName}" action: rename/delete`, 'rename');
                    if (!action) return;
                    if (action === 'rename') {
                        const newName = window.prompt('New folder name', folderName);
                        if (!newName) return;
                        await fetch(config.folderRenameUrlTemplate.replace('__ID__', folderId), {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                            body: JSON.stringify({ name: newName }),
                        });
                    }
                    if (action === 'delete') {
                        if (!window.confirm('Delete this folder?')) return;
                        await fetch(config.folderDeleteUrlTemplate.replace('__ID__', folderId), {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                        });
                    }
                    window.location.reload();
                },
            };
        }
    </script>
</x-app-layout>
