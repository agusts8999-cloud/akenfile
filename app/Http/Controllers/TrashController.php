<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use App\Services\FileService;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderService $folderService
    ) {
    }

    public function index(): View
    {
        $user = auth()->user();

        return view('trash.index', [
            'trashedFiles' => $this->fileService->listTrash($user),
            'trashedFolders' => $this->folderService->listTrash($user),
        ]);
    }

    public function restoreFile(int $fileId): RedirectResponse
    {
        $file = File::onlyTrashed()->findOrFail($fileId);
        $this->authorize('delete', $file);
        $this->fileService->restore($file);

        return back()->with('status', 'File restored.');
    }

    public function restoreFolder(int $folderId): RedirectResponse
    {
        $folder = Folder::onlyTrashed()->findOrFail($folderId);
        $this->authorize('delete', $folder);
        $this->folderService->restore($folder);

        return back()->with('status', 'Folder restored.');
    }

    public function forceDeleteFile(int $fileId): RedirectResponse
    {
        $file = File::onlyTrashed()->findOrFail($fileId);
        $this->authorize('delete', $file);
        $this->fileService->forceDelete($file);

        return back()->with('status', 'File deleted permanently.');
    }

    public function forceDeleteFolder(int $folderId): RedirectResponse
    {
        $folder = Folder::onlyTrashed()->findOrFail($folderId);
        $this->authorize('delete', $folder);
        $this->folderService->forceDelete($folder);

        return back()->with('status', 'Folder deleted permanently.');
    }
}
