<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Models\File;
use App\Models\Folder;
use App\Services\FileService;
use App\Services\FolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderService $folderService
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', File::class);

        $folderId = $request->integer('folder_id') ?: null;
        $currentFolder = $folderId ? Folder::query()->findOrFail($folderId) : null;

        if ($currentFolder) {
            $this->authorize('view', $currentFolder);
        }

        $files = $this->fileService->listForUser($request->user(), $folderId, $request->string('search')->toString());
        $folders = $this->folderService->listForUser($request->user(), $folderId);
        $breadcrumbs = $currentFolder ? $this->folderService->breadcrumbs($currentFolder) : [];
        $storageQuery = File::query();

        if (! $request->user()->isAdmin()) {
            $storageQuery->where('user_id', $request->user()->id);
        }

        $storageUsedBytes = (int) $storageQuery->sum('size');

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'folders' => $folders,
                    'files' => $files->items(),
                ],
                'meta' => [
                    'page' => $files->currentPage(),
                    'per_page' => $files->perPage(),
                    'total' => $files->total(),
                ],
            ]);
        }

        return view('files.index', compact('files', 'folders', 'breadcrumbs', 'currentFolder', 'storageUsedBytes'));
    }

    public function upload(StoreFileRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', File::class);

        $folder = null;
        if ($request->filled('folder_id')) {
            $folder = Folder::query()->findOrFail((int) $request->input('folder_id'));
            $this->authorize('view', $folder);
        }

        $file = $this->fileService->upload($request->user(), $request->file('file'), $folder);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['data' => $file], 201);
        }

        return back()->with('status', 'File uploaded.');
    }

    public function download(Request $request, File $file)
    {
        $this->authorize('view', $file);

        return $this->fileService->download($file);
    }

    public function rename(UpdateFileRequest $request, File $file): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $file);
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        $updated = $this->fileService->rename($file, (string) $request->input('name'));

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'File renamed.');
    }

    public function move(UpdateFileRequest $request, File $file): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $file);
        $targetFolder = null;

        if ($request->filled('folder_id')) {
            $targetFolder = Folder::query()->findOrFail((int) $request->input('folder_id'));
            $this->authorize('view', $targetFolder);
        }

        $updated = $this->fileService->move($file, $targetFolder);

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'File moved.');
    }

    public function destroy(Request $request, File $file): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $file);
        $this->fileService->delete($file);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'File deleted.']);
        }

        return back()->with('status', 'File deleted.');
    }
}
