<x-app-layout>
    <div class="space-y-4">
        <h1 class="text-xl font-semibold flex items-center gap-2">
            <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 8a3 3 0 1 0-2.83-4H13a3 3 0 0 0 .17 1L8.7 8.1A3 3 0 0 0 6 7a3 3 0 1 0 2.7 4.5l4.48 3.1a3 3 0 1 0 .85-1.22L9.55 10.3A3 3 0 0 0 9 9c0-.21.02-.42.06-.62l4.46-3.06A3 3 0 0 0 16 8Z"/></svg>
            <span>Shared</span>
        </h1>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">Share File or Folder to User</h2>
            <form method="POST" action="{{ route('shared.user.store') }}" class="grid md:grid-cols-4 gap-3">
                @csrf
                <select name="shared_item" required class="rounded-lg border-slate-300 text-sm">
                    <option value="">Select file/folder</option>
                    <optgroup label="Files">
                        @foreach($myFiles as $file)
                            <option value="file:{{ $file->id }}">[File] {{ $file->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Folders">
                        @foreach($myFolders as $folder)
                            <option value="folder:{{ $folder->id }}">[Folder] {{ $folder->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <select name="target_user_id" required class="rounded-lg border-slate-300 text-sm">
                    <option value="">Select user</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <select name="permission" class="rounded-lg border-slate-300 text-sm">
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                </select>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Share</button>
            </form>
            <p class="text-xs text-slate-400 mt-2">Jika memilih folder, sistem akan membagikan semua file di dalam folder tersebut.</p>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">My Internal Shares</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">File</th>
                            <th class="px-3 py-2 text-left">Shared To</th>
                            <th class="px-3 py-2 text-left">Permission</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($myShares as $share)
                            <tr>
                                <td class="px-3 py-2">{{ $share->file?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $share->targetUser?->email ?? '-' }}</td>
                                <td class="px-3 py-2 uppercase">{{ $share->permission }}</td>
                                <td class="px-3 py-2 text-right">
                                    <form method="POST" action="{{ route('shared.user.destroy', $share) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 text-xs">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No internal shares yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-3">Public Links</h2>
            <form method="POST" action="{{ route('shared.public-link.store') }}" class="grid md:grid-cols-4 gap-3 mb-4">
                @csrf
                <select name="shared_item" required class="rounded-lg border-slate-300 text-sm">
                    <option value="">Select file/folder</option>
                    <optgroup label="Files">
                        @foreach($myFiles as $file)
                            <option value="file:{{ $file->id }}">[File] {{ $file->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Folders">
                        @foreach($myFolders as $folder)
                            <option value="folder:{{ $folder->id }}">[Folder] {{ $folder->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <input name="expires_at" type="datetime-local" class="rounded-lg border-slate-300 text-sm">
                <input name="password" type="text" placeholder="Optional password" class="rounded-lg border-slate-300 text-sm">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Create Link</button>
            </form>
            <div class="space-y-2">
                @forelse($publicLinks as $link)
                    <div class="border rounded-lg p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div class="text-sm">
                            <div class="font-medium">{{ $link->file?->name ?? '-' }}</div>
                            <div class="text-slate-500 text-xs">{{ route('public-share.download', $link->token) }}</div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('shared.public-link.send-email', $link) }}" class="flex gap-2">
                                @csrf
                                <input name="recipient_email" type="email" required placeholder="recipient email" class="rounded border-slate-300 text-xs">
                                <button class="text-indigo-600 text-xs">Send Link</button>
                            </form>
                            <form method="POST" action="{{ route('shared.public-link.destroy', $link) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 text-xs">Revoke</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">No public links created.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-2">Shared With Me</h2>
            <ul class="text-sm text-slate-600 space-y-1">
                @forelse($sharedWithMe as $file)
                    <li>{{ $file->name }} - owner: {{ $file->user?->email }}</li>
                @empty
                    <li class="text-slate-500">No files shared with you.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
