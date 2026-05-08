<x-app-layout>
    <div x-data="fileManager({
        uploadUrl: '{{ route('files.upload', [], false) }}',
        filePreviewUrlTemplate: '{{ route('files.preview', ['file' => '__ID__'], false) }}',
        fileContentUrlTemplate: '{{ route('files.content', ['file' => '__ID__'], false) }}',
        fileContentUpdateUrlTemplate: '{{ route('files.content.update', ['file' => '__ID__'], false) }}',
        fileRenameUrlTemplate: '{{ route('files.rename', ['file' => '__ID__'], false) }}',
        fileMoveUrlTemplate: '{{ route('files.move', ['file' => '__ID__'], false) }}',
        fileDeleteUrlTemplate: '{{ route('files.destroy', ['file' => '__ID__'], false) }}',
        folderRenameUrlTemplate: '{{ route('folders.rename', ['folder' => '__ID__'], false) }}',
        folderDeleteUrlTemplate: '{{ route('folders.destroy', ['folder' => '__ID__'], false) }}',
        folderId: '{{ $currentFolder?->id }}',
        csrf: '{{ csrf_token() }}',
        bulkDeleteUrl: '{{ route('files.bulk.delete', [], false) }}',
        bulkCopyUrl: '{{ route('files.bulk.copy', [], false) }}',
        bulkMoveUrl: '{{ route('files.bulk.move', [], false) }}',
        previewDialogWidthPx: @js((int) ($previewDialogWidthPx ?? 500)),
        fileThumbnailSizePx: @js((int) ($fileThumbnailSizePx ?? 48)),
        moveTargets: @js($moveTargets ?? []),
        items: @js(collect($folders->map(fn($folder) => ['type' => 'folder', 'id' => $folder->id]))->merge($files->map(fn($file) => ['type' => 'file', 'id' => $file->id]))->values())
    })" class="space-y-4">
        <template x-if="isProcessing">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35">
                <div class="rounded-xl bg-white px-5 py-4 shadow-xl border text-center min-w-[220px]">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-slate-200 border-t-indigo-600"></div>
                    <div class="mt-2 text-sm font-medium text-slate-700" x-text="processingText || 'Processing...' "></div>
                </div>
            </div>
        </template>

        <template x-if="actionError">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700 flex items-center justify-between">
                <span x-text="actionError"></span>
                <button type="button" class="text-red-600" @click="actionError = ''">Dismiss</button>
            </div>
        </template>

        <template x-if="showMoveDialog">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="w-full max-w-md rounded-xl border bg-white p-4 shadow-xl">
                    <div class="text-base font-semibold text-slate-800" x-text="moveDialogTitle || 'Select target folder'"></div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Target folder</label>
                        <select x-model="moveDialogTargetId" class="w-full rounded-lg border px-3 py-2 text-sm">
                            <option value="">Root</option>
                            <template x-for="target in moveTargetOptions" :key="target.id">
                                <option :value="String(target.id)" x-text="target.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-lg border px-3 py-1.5 text-sm" @click="closeMoveDialog()">Cancel</button>
                        <button type="button" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm text-white" @click="confirmMoveDialog()">Move</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="showPreviewModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                <div class="rounded-xl border bg-white shadow-xl overflow-hidden" :style="previewDialogStyle()">
                    <div class="z-20 flex items-center justify-between border-b bg-white px-3 py-2">
                        <div class="font-semibold text-slate-800 truncate pr-4" x-text="previewFile.name || 'File preview'"></div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded border px-2 py-1 text-xs" @click="zoomOutPreview()" :disabled="previewZoom <= 0.5">-</button>
                            <button type="button" class="rounded border px-2 py-1 text-xs min-w-[56px]" @click="resetPreviewZoom()">
                                <span x-text="`${Math.round(previewZoom * 100)}%`"></span>
                            </button>
                            <button type="button" class="rounded border px-2 py-1 text-xs" @click="zoomInPreview()" :disabled="previewZoom >= 3">+</button>
                            <button type="button" class="rounded border px-2 py-1 text-xs" @click="closePreview()">Close</button>
                        </div>
                    </div>
                    <div class="overflow-auto p-4" x-ref="previewViewport" :style="previewViewportStyle()">
                        <template x-if="previewLoading">
                            <div class="py-10 text-center text-sm text-slate-500">Loading preview...</div>
                        </template>
                        <template x-if="!previewLoading && previewError">
                            <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="previewError"></div>
                        </template>
                        <template x-if="!previewLoading && !previewError && previewFile.is_previewable_image">
                            <div class="flex min-h-full min-w-full items-start justify-start">
                                <div class="shrink-0" :style="`width: ${previewRenderWidth()}px; height: ${previewRenderHeight()}px;`">
                                    <img :src="previewFile.preview_url" :alt="previewFile.name" class="block h-full w-full rounded border object-contain" @load="setAutoFitZoom($event)">
                                </div>
                            </div>
                        </template>
                        <template x-if="!previewLoading && !previewError && !previewFile.is_previewable_image">
                            <div class="text-sm text-slate-600">
                                Preview image hanya tersedia untuk file gambar.
                                <a class="ml-2 text-indigo-600 hover:underline" :href="previewFile.download_url">Download file</a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="showEditorModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                <div class="w-full max-w-5xl rounded-xl border bg-white shadow-xl overflow-hidden">
                    <div class="flex items-center justify-between border-b px-4 py-3">
                        <div class="font-semibold text-slate-800 truncate pr-4" x-text="editorFile.name || 'Edit file'"></div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="rounded border px-3 py-1.5 text-sm" @click="closeEditor()">Close</button>
                            <button type="button" class="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white disabled:opacity-60" :disabled="editorSaving || editorLoading" @click="saveEditorContent()">
                                <span x-text="editorSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[78vh] overflow-auto p-4">
                        <template x-if="editorLoading">
                            <div class="py-10 text-center text-sm text-slate-500">Loading editor...</div>
                        </template>
                        <template x-if="!editorLoading && editorError">
                            <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="editorError"></div>
                        </template>
                        <template x-if="!editorLoading && !editorError">
                            <textarea x-ref="tinyEditor" class="hidden" x-model="editorContent"></textarea>
                        </template>
                    </div>
                </div>
            </div>
        </template>

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

        <template x-if="selectedCount() > 0">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 flex items-center justify-between">
                <div class="text-sm text-indigo-700">
                    <span class="font-semibold" x-text="selectedCount()"></span> item selected
                    <span class="text-indigo-500" x-text="selectedBreakdown()"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-sm" @click="bulkCopy()">Copy selected</button>
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-sm" @click="bulkMove()">Move selected</button>
                    <button type="button" class="rounded-lg border border-red-300 px-3 py-1.5 text-sm text-red-700" @click="bulkDelete()">Delete selected</button>
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-sm" @click="clearSelection()">Clear</button>
                </div>
            </div>
        </template>

        <div class="rounded-xl border bg-white p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold flex items-center gap-2">
                    <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                    <span>Folders</span>
                </h3>
                <div class="flex gap-2">
                    <button type="button" @click="folderViewMode='grid'" class="rounded border px-2 py-1 text-xs" :class="folderViewMode === 'grid' ? 'bg-indigo-600 text-white border-indigo-600' : ''">Grid</button>
                    <button type="button" @click="folderViewMode='list'" class="rounded border px-2 py-1 text-xs" :class="folderViewMode === 'list' ? 'bg-indigo-600 text-white border-indigo-600' : ''">List</button>
                </div>
            </div>
            <div x-show="folderViewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @forelse ($folders as $folder)
                    <div class="rounded-lg border p-3" :class="isSelected('folder', {{ $folder->id }}) ? 'border-indigo-400 bg-indigo-50' : ''">
                        <div class="flex items-start justify-between">
                            <input type="checkbox" class="mt-0.5" :checked="isSelected('folder', {{ $folder->id }})" @click="toggleItemSelection('folder', {{ $folder->id }}, $event)">
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
            <div x-show="folderViewMode === 'list'" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-left w-10"></th>
                            <th class="px-4 py-2 text-left">Folder Name</th>
                            <th class="px-4 py-2 text-left">Items</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($folders as $folder)
                            <tr class="hover:bg-slate-50" :class="isSelected('folder', {{ $folder->id }}) ? 'bg-indigo-50' : ''">
                                <td class="px-3 py-3"><input type="checkbox" :checked="isSelected('folder', {{ $folder->id }})" @click="toggleItemSelection('folder', {{ $folder->id }}, $event)"></td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('files.index', ['folder_id' => $folder->id]) }}" class="font-medium text-sm hover:text-indigo-600 truncate inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6h5l2 2h8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/></svg>
                                        <span class="truncate">{{ $folder->name }}</span>
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $folder->children_count + $folder->files_count }} items</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="rounded border px-2 py-1 text-xs" @click="folderAction({{ $folder->id }}, '{{ $folder->name }}')">Manage</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-500">No folders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                <div class="px-4 py-2 border-b bg-slate-50/70">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" :checked="isAllSelected()" @click.prevent="toggleSelectAll()">
                        <span>Select all (files + folders)</span>
                    </label>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-left w-10"></th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Size</th>
                            <th class="px-4 py-2 text-left">Last Modified</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($files as $file)
                            <tr class="hover:bg-slate-50" :class="isSelected('file', {{ $file->id }}) ? 'bg-indigo-50' : ''">
                                <td class="px-3 py-3"><input type="checkbox" :checked="isSelected('file', {{ $file->id }})" @click="toggleItemSelection('file', {{ $file->id }}, $event)"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($file->is_previewable_image && $file->thumbnail_url)
                                            <img src="{{ $file->thumbnail_url }}" alt="{{ $file->name }}" class="shrink-0 rounded border object-cover" :style="thumbnailStyle()">
                                        @else
                                            <svg class="h-4 w-4 text-sky-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                                        @endif
                                        <div class="min-w-0">
                                            <span class="truncate block">{{ $file->name }}</span>
                                            @if($file->is_editable_text)
                                                <span class="mt-0.5 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Editable</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ number_format($file->size / 1024 / 1024, 2) }} MB</td>
                                <td class="px-4 py-3">{{ $file->updated_at->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end items-center gap-2 text-xs">
                                        <button type="button" class="rounded border px-2 py-1 disabled:opacity-40 disabled:cursor-not-allowed" @click="openPreview({{ $file->id }})" @disabled(! $file->is_previewable_image)>Preview</button>
                                        <button type="button" class="rounded border px-2 py-1 disabled:opacity-40 disabled:cursor-not-allowed" @click="openEditor({{ $file->id }})" @disabled(! $file->is_editable_text)>Edit</button>
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
                    <div class="rounded-lg border p-3" :class="isSelected('file', {{ $file->id }}) ? 'border-indigo-400 bg-indigo-50' : ''">
                        <div class="flex items-center justify-between mb-1"><input type="checkbox" :checked="isSelected('file', {{ $file->id }})" @click="toggleItemSelection('file', {{ $file->id }}, $event)"></div>
                        <div class="mb-2 flex justify-center">
                            @if($file->is_previewable_image && $file->thumbnail_url)
                                <img src="{{ $file->thumbnail_url }}" alt="{{ $file->name }}" class="rounded border object-cover" :style="gridThumbnailStyle()">
                            @else
                                <div class="flex items-center justify-center rounded border bg-slate-50 text-slate-400" :style="gridThumbnailStyle()">
                                    <svg class="h-5 w-5 text-sky-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="font-medium text-sm truncate">
                            <span class="truncate">{{ $file->name }}</span>
                            @if($file->is_editable_text)
                                <span class="mt-1 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Editable</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400 mt-1">{{ number_format($file->size / 1024 / 1024, 2) }} MB</div>
                        <div class="mt-3 flex gap-2 text-xs">
                            <button type="button" class="rounded border px-2 py-1 disabled:opacity-40 disabled:cursor-not-allowed" @click="openPreview({{ $file->id }})" @disabled(! $file->is_previewable_image)>Preview</button>
                            <button type="button" class="rounded border px-2 py-1 disabled:opacity-40 disabled:cursor-not-allowed" @click="openEditor({{ $file->id }})" @disabled(! $file->is_editable_text)>Edit</button>
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
                items: config.items || [],
                moveTargetOptions: config.moveTargets || [],
                showMoveDialog: false,
                moveDialogTitle: '',
                moveDialogTargetId: '',
                moveDialogHandler: null,
                viewMode: 'table',
                folderViewMode: 'grid',
                queue: [],
                selectedItems: {},
                lastSelectedIndex: null,
                activeUploads: 0,
                maxParallel: 3,
                isProcessing: false,
                processingText: '',
                actionError: '',
                showPreviewModal: false,
                previewLoading: false,
                previewError: '',
                previewFile: {},
                previewBaseZoom: 1,
                previewZoom: 1,
                previewNaturalWidth: 0,
                previewNaturalHeight: 0,
                showEditorModal: false,
                editorLoading: false,
                editorSaving: false,
                editorError: '',
                editorFile: {},
                editorContent: '',
                tinyMceInstance: null,
                tinyMceScriptLoading: false,
                tinyMceScriptLoaded: false,
                previewDialogWidthPx: Number(config.previewDialogWidthPx || 500),
                fileThumbnailSizePx: Number(config.fileThumbnailSizePx || 48),

                startProcessing(text = 'Processing...') {
                    this.processingText = text;
                    this.isProcessing = true;
                },
                stopProcessing() {
                    this.isProcessing = false;
                    this.processingText = '';
                },
                extractErrorMessage(payload, fallback = 'Request failed') {
                    if (!payload) return fallback;
                    if (payload.message) return payload.message;
                    if (payload.errors && typeof payload.errors === 'object') {
                        const firstKey = Object.keys(payload.errors)[0];
                        if (firstKey && payload.errors[firstKey]?.[0]) return payload.errors[firstKey][0];
                    }
                    return fallback;
                },
                previewDialogStyle() {
                    const width = Math.max(200, Math.min(1200, Number(this.previewDialogWidthPx || 500)));
                    return `width: ${width}px; max-width: ${width}px;`;
                },
                previewViewportStyle() {
                    const width = Math.max(200, Math.min(1200, Number(this.previewDialogWidthPx || 500)));
                    return `width: ${width}px; height: ${width}px; max-width: ${width}px; max-height: ${width}px;`;
                },
                thumbnailStyle() {
                    const size = Math.max(24, Math.min(160, Number(this.fileThumbnailSizePx || 48)));
                    return `width: ${size}px; height: ${size}px;`;
                },
                gridThumbnailStyle() {
                    const size = Math.max(24, Math.min(160, Number(this.fileThumbnailSizePx || 48)));
                    return `width: ${size}px; height: ${size}px;`;
                },
                async fetchJson(url, { method = 'GET', body = null } = {}) {
                    let normalizedMethod = (method || 'GET').toUpperCase();
                    let useMethodSpoof = ['PATCH', 'PUT', 'DELETE'].includes(normalizedMethod);
                    let requestMethod = useMethodSpoof ? 'POST' : normalizedMethod;
                    let requestBody = useMethodSpoof ? { ...(body || {}), _method: normalizedMethod } : body;
                    const formBody = new URLSearchParams();

                    if (requestBody && typeof requestBody === 'object') {
                        Object.entries(requestBody).forEach(([key, value]) => {
                            if (Array.isArray(value)) {
                                value.forEach((item) => formBody.append(`${key}[]`, item ?? ''));
                                return;
                            }
                            if (value === null || value === undefined) {
                                formBody.append(key, '');
                                return;
                            }
                            formBody.append(key, value);
                        });
                    }

                    const response = await fetch(url, {
                        method: requestMethod,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-CSRF-TOKEN': config.csrf,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formBody.toString() || null,
                    });

                    let payload = null;
                    try {
                        payload = await response.json();
                    } catch (e) {
                        payload = null;
                    }

                    if (!response.ok) {
                        throw new Error(this.extractErrorMessage(payload, `Request failed (${response.status})`));
                    }

                    return payload;
                },
                async requestJson(url, { method = 'GET', body = null, loadingText = 'Processing...', reloadOnSuccess = true } = {}) {
                    this.actionError = '';
                    this.startProcessing(loadingText);
                    let normalizedMethod = (method || 'GET').toUpperCase();
                    let useMethodSpoof = ['PATCH', 'PUT', 'DELETE'].includes(normalizedMethod);
                    let requestMethod = useMethodSpoof ? 'POST' : normalizedMethod;
                    let requestBody = useMethodSpoof ? { ...(body || {}), _method: normalizedMethod } : body;

                    try {
                        const formBody = new URLSearchParams();
                        if (requestBody && typeof requestBody === 'object') {
                            Object.entries(requestBody).forEach(([key, value]) => {
                                if (Array.isArray(value)) {
                                    value.forEach((item) => formBody.append(`${key}[]`, item ?? ''));
                                    return;
                                }
                                if (value === null || value === undefined) {
                                    formBody.append(key, '');
                                    return;
                                }
                                formBody.append(key, value);
                            });
                        }

                        const response = await fetch(url, {
                            method: requestMethod,
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formBody.toString() || null,
                        });
                        let payload = null;
                        try {
                            payload = await response.json();
                        } catch (e) {
                            payload = null;
                        }

                        if (!response.ok) {
                            const msg = this.extractErrorMessage(payload, `Request failed (${response.status})`);
                            this.actionError = msg;
                            window.alert(msg);
                            return { ok: false, payload };
                        }

                        if (reloadOnSuccess) {
                            window.location.reload();
                        }
                        return { ok: true, payload };
                    } catch (error) {
                        const msg = error?.message ? `Network error: ${error.message}` : 'Network error. Please try again.';
                        const urlInfo = `URL: ${url}`;
                        const methodInfo = `Method: ${requestMethod}`;
                        const alertMsg = `${msg}
${urlInfo}
${methodInfo}`;
                        this.actionError = msg;
                        window.alert(alertMsg);
                        return { ok: false, payload: null };
                    } finally {
                        this.stopProcessing();
                    }
                },
                async ensureTinyMce() {
                    if (this.tinyMceScriptLoaded && window.tinymce) {
                        return;
                    }
                    if (this.tinyMceScriptLoading) {
                        await new Promise((resolve) => {
                            const wait = () => {
                                if (this.tinyMceScriptLoaded && window.tinymce) {
                                    resolve();
                                    return;
                                }
                                setTimeout(wait, 50);
                            };
                            wait();
                        });
                        return;
                    }

                    this.tinyMceScriptLoading = true;
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
                        script.referrerPolicy = 'origin';
                        script.onload = () => {
                            this.tinyMceScriptLoaded = true;
                            this.tinyMceScriptLoading = false;
                            resolve();
                        };
                        script.onerror = () => {
                            this.tinyMceScriptLoading = false;
                            reject(new Error('Failed to load TinyMCE.'));
                        };
                        document.head.appendChild(script);
                    });
                },
                destroyTinyMceEditor() {
                    if (this.tinyMceInstance) {
                        this.tinyMceInstance.remove();
                        this.tinyMceInstance = null;
                    }
                },
                async initTinyMceEditor() {
                    await this.ensureTinyMce();
                    this.destroyTinyMceEditor();
                    const target = this.$refs.tinyEditor;
                    if (!target || !window.tinymce) return;
                    target.value = this.editorContent || '';

                    await window.tinymce.init({
                        target,
                        menubar: true,
                        branding: false,
                        height: 520,
                        plugins: 'lists link code table searchreplace autolink',
                        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | code',
                    });

                    this.tinyMceInstance = window.tinymce.get(target.id) || window.tinymce.activeEditor || null;
                    if (this.tinyMceInstance) {
                        this.tinyMceInstance.setContent(this.editorContent || '');
                    }
                },
                async openPreview(fileId) {
                    this.showPreviewModal = true;
                    this.previewLoading = true;
                    this.previewError = '';
                    this.previewFile = {};
                    this.previewBaseZoom = 1;
                    this.previewZoom = 1;
                    this.previewNaturalWidth = 0;
                    this.previewNaturalHeight = 0;
                    try {
                        const payload = await this.fetchJson(config.filePreviewUrlTemplate.replace('__ID__', fileId));
                        this.previewFile = payload?.data || {};
                    } catch (error) {
                        this.previewError = error?.message || 'Failed to load preview.';
                    } finally {
                        this.previewLoading = false;
                    }
                },
                closePreview() {
                    this.showPreviewModal = false;
                    this.previewLoading = false;
                    this.previewError = '';
                    this.previewFile = {};
                    this.previewBaseZoom = 1;
                    this.previewZoom = 1;
                    this.previewNaturalWidth = 0;
                    this.previewNaturalHeight = 0;
                },
                setAutoFitZoom(event) {
                    const img = event?.target;
                    const viewport = this.$refs.previewViewport;
                    if (!img || !viewport) {
                        this.previewBaseZoom = 1;
                        return;
                    }

                    const viewportWidth = Math.max(1, viewport.clientWidth - 24);
                    const viewportHeight = Math.max(1, viewport.clientHeight - 24);
                    const naturalWidth = Math.max(1, img.naturalWidth || img.width);
                    const naturalHeight = Math.max(1, img.naturalHeight || img.height);

                    this.previewNaturalWidth = naturalWidth;
                    this.previewNaturalHeight = naturalHeight;

                    const fitScale = Math.min(1, viewportWidth / naturalWidth, viewportHeight / naturalHeight);
                    this.previewBaseZoom = Number.isFinite(fitScale) ? fitScale : 1;
                },
                previewRenderWidth() {
                    const width = this.previewNaturalWidth || 1;
                    return Math.max(1, Math.round(width * this.previewBaseZoom * this.previewZoom));
                },
                previewRenderHeight() {
                    const height = this.previewNaturalHeight || 1;
                    return Math.max(1, Math.round(height * this.previewBaseZoom * this.previewZoom));
                },
                zoomInPreview() {
                    this.previewZoom = Math.min(3, Number((this.previewZoom + 0.1).toFixed(2)));
                },
                zoomOutPreview() {
                    this.previewZoom = Math.max(0.5, Number((this.previewZoom - 0.1).toFixed(2)));
                },
                resetPreviewZoom() {
                    this.previewZoom = 1;
                },
                async openEditor(fileId) {
                    this.showEditorModal = true;
                    this.editorLoading = true;
                    this.editorSaving = false;
                    this.editorError = '';
                    this.editorFile = {};
                    this.editorContent = '';
                    try {
                        const previewPayload = await this.fetchJson(config.filePreviewUrlTemplate.replace('__ID__', fileId));
                        if (!previewPayload?.data?.is_editable) {
                            throw new Error('File ini tidak mendukung editor TinyMCE.');
                        }

                        const contentPayload = await this.fetchJson(config.fileContentUrlTemplate.replace('__ID__', fileId));
                        this.editorFile = previewPayload.data;
                        this.editorContent = contentPayload?.data?.content || '';

                        this.$nextTick(async () => {
                            try {
                                await this.initTinyMceEditor();
                            } catch (initError) {
                                this.editorError = initError?.message || 'Failed to initialize TinyMCE.';
                            }
                        });
                    } catch (error) {
                        this.editorError = error?.message || 'Failed to open editor.';
                    } finally {
                        this.editorLoading = false;
                    }
                },
                closeEditor() {
                    this.showEditorModal = false;
                    this.editorLoading = false;
                    this.editorSaving = false;
                    this.editorError = '';
                    this.editorFile = {};
                    this.editorContent = '';
                    this.destroyTinyMceEditor();
                },
                async saveEditorContent() {
                    if (!this.editorFile?.id) return;
                    this.editorSaving = true;
                    this.editorError = '';
                    try {
                        if (this.tinyMceInstance) {
                            this.editorContent = this.tinyMceInstance.getContent({ format: 'html' });
                        }
                        await this.fetchJson(config.fileContentUpdateUrlTemplate.replace('__ID__', this.editorFile.id), {
                            method: 'PATCH',
                            body: { content: this.editorContent },
                        });
                        this.closeEditor();
                        window.location.reload();
                    } catch (error) {
                        this.editorError = error?.message || 'Failed to save content.';
                    } finally {
                        this.editorSaving = false;
                    }
                },

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

                itemKey(type, id) {
                    return `${type}:${id}`;
                },
                isSelected(type, id) {
                    return !!this.selectedItems[this.itemKey(type, id)];
                },
                selectedCount() {
                    return Object.keys(this.selectedItems).length;
                },
                selectedBreakdown() {
                    return `(${this.selectedFileIds().length} files, ${this.selectedFolderIds().length} folders)`;
                },
                isAllSelected() {
                    return this.items.length > 0 && this.selectedCount() === this.items.length;
                },
                toggleSelectAll() {
                    if (this.isAllSelected()) {
                        this.selectedItems = {};
                        this.lastSelectedIndex = null;
                        return;
                    }
                    const next = {};
                    this.items.forEach((item, idx) => {
                        next[this.itemKey(item.type, item.id)] = true;
                        this.lastSelectedIndex = idx;
                    });
                    this.selectedItems = next;
                },
                toggleItemSelection(type, id, event) {
                    const key = this.itemKey(type, id);
                    const index = this.items.findIndex((item) => item.type === type && item.id === id);
                    if (index === -1) return;
                    const isCheckboxClick = event?.target?.type === 'checkbox';

                    if (event.shiftKey && this.lastSelectedIndex !== null) {
                        const start = Math.min(this.lastSelectedIndex, index);
                        const end = Math.max(this.lastSelectedIndex, index);
                        const shouldSelect = !this.selectedItems[key];
                        const next = { ...this.selectedItems };
                        for (let i = start; i <= end; i += 1) {
                            const item = this.items[i];
                            const rangeKey = this.itemKey(item.type, item.id);
                            if (shouldSelect) {
                                next[rangeKey] = true;
                            } else {
                                delete next[rangeKey];
                            }
                        }
                        this.selectedItems = next;
                        this.lastSelectedIndex = index;
                        return;
                    }

                    if (isCheckboxClick) {
                        const next = { ...this.selectedItems };
                        if (next[key]) {
                            delete next[key];
                        } else {
                            next[key] = true;
                        }
                        this.selectedItems = next;
                        this.lastSelectedIndex = index;
                        return;
                    }

                    if (!event.ctrlKey && !event.metaKey && !event.shiftKey) {
                        this.selectedItems = { [key]: true };
                        this.lastSelectedIndex = index;
                        return;
                    }

                    const next = { ...this.selectedItems };
                    if (next[key]) {
                        delete next[key];
                    } else {
                        next[key] = true;
                    }
                    this.selectedItems = next;
                    this.lastSelectedIndex = index;
                },
                clearSelection() {
                    this.selectedItems = {};
                    this.lastSelectedIndex = null;
                },
                selectedFileIds() {
                    return Object.keys(this.selectedItems)
                        .filter((key) => key.startsWith('file:'))
                        .map((key) => Number(key.replace('file:', '')));
                },
                selectedFolderIds() {
                    return Object.keys(this.selectedItems)
                        .filter((key) => key.startsWith('folder:'))
                        .map((key) => Number(key.replace('folder:', '')));
                },
                async runBulkAction(url, targetFolderId = null, loadingText = 'Processing bulk action...') {
                    return this.requestJson(url, {
                        method: 'POST',
                        loadingText,
                        body: {
                            file_ids: this.selectedFileIds(),
                            folder_ids: this.selectedFolderIds(),
                            target_folder_id: targetFolderId,
                        },
                    });
                },
                async bulkDelete() {
                    if (!window.confirm('Delete selected items?')) return;
                    await this.runBulkAction(config.bulkDeleteUrl, null, 'Deleting selected items...');
                },
                async bulkMove() {
                    this.openMoveDialog('Move selected items', async (folderId) => {
                        await this.runBulkAction(config.bulkMoveUrl, folderId, 'Moving selected items...');
                    });
                },
                async bulkCopy() {
                    const folderId = window.prompt('Copy to folder ID (empty = root)', '');
                    await this.runBulkAction(config.bulkCopyUrl, folderId || null, 'Copying selected items...');
                },
                openMoveDialog(title, handler) {
                    this.moveDialogTitle = title;
                    this.moveDialogTargetId = '';
                    this.moveDialogHandler = handler;
                    this.showMoveDialog = true;
                },
                closeMoveDialog() {
                    this.showMoveDialog = false;
                    this.moveDialogTitle = '';
                    this.moveDialogTargetId = '';
                    this.moveDialogHandler = null;
                },
                async confirmMoveDialog() {
                    if (!this.moveDialogHandler) return;
                    const targetId = this.moveDialogTargetId === '' ? null : Number(this.moveDialogTargetId);
                    const handler = this.moveDialogHandler;
                    this.closeMoveDialog();
                    await handler(targetId);
                },

                formatSize(bytes) {
                    if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
                    if (bytes >= 1024) return `${(bytes / 1024).toFixed(2)} KB`;
                    return `${bytes} B`;
                },
                createFolder() {
                    const name = window.prompt('Folder name');
                    if (!name) return;
                    this.startProcessing('Creating folder...');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('folders.store', [], false) }}';
                    form.innerHTML = `<input type="hidden" name="_token" value="${config.csrf}">
                        <input type="hidden" name="name" value="${name}">
                        <input type="hidden" name="parent_id" value="${config.folderId || ''}">`;
                    document.body.appendChild(form);
                    form.submit();
                },
                async renameFile(fileId, oldName) {
                    const name = window.prompt('Rename file', oldName);
                    if (!name || name === oldName) return;
                    await this.requestJson(config.fileRenameUrlTemplate.replace('__ID__', fileId), {
                        method: 'PATCH',
                        loadingText: 'Renaming file...',
                        body: { name },
                    });
                },
                async moveFile(fileId) {
                    this.openMoveDialog('Move file', async (folderId) => {
                        await this.requestJson(config.fileMoveUrlTemplate.replace('__ID__', fileId), {
                            method: 'PATCH',
                            loadingText: 'Moving file...',
                            body: { folder_id: folderId },
                        });
                    });
                },
                async deleteFile(fileId) {
                    if (!window.confirm('Delete this file?')) return;
                    await this.requestJson(config.fileDeleteUrlTemplate.replace('__ID__', fileId), {
                        method: 'DELETE',
                        loadingText: 'Deleting file...',
                    });
                },
                async folderAction(folderId, folderName) {
                    const action = window.prompt(`Folder "${folderName}" action: rename/delete`, 'rename');
                    if (!action) return;
                    if (action === 'rename') {
                        const newName = window.prompt('New folder name', folderName);
                        if (!newName) return;
                        await this.requestJson(config.folderRenameUrlTemplate.replace('__ID__', folderId), {
                            method: 'PATCH',
                            loadingText: 'Renaming folder...',
                            body: { name: newName },
                        });
                        return;
                    }
                    if (action === 'delete') {
                        if (!window.confirm('Delete this folder?')) return;
                        await this.requestJson(config.folderDeleteUrlTemplate.replace('__ID__', folderId), {
                            method: 'DELETE',
                            loadingText: 'Deleting folder...',
                        });
                        return;
                    }
                    window.alert('Unknown action. Use rename or delete.');
                },
            };
        }
    </script>
</x-app-layout>
