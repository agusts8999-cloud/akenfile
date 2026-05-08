<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\Folder;
use App\Services\FolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function __construct(private readonly FolderService $folderService)
    {
    }

    public function store(StoreFolderRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Folder::class);

        $parent = null;
        if ($request->filled('parent_id')) {
            $parent = Folder::query()->findOrFail((int) $request->input('parent_id'));
            $this->authorize('view', $parent);
        }

        $folder = $this->folderService->create($request->user(), (string) $request->input('name'), $parent);

        if ($request->expectsJson()) {
            return response()->json(['data' => $folder], 201);
        }

        return back()->with('status', 'Folder created.');
    }

    public function rename(UpdateFolderRequest $request, Folder $folder): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $folder);
        $updated = $this->folderService->rename($folder, (string) $request->input('name'));

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'Folder renamed.');
    }

    public function destroy(Request $request, Folder $folder): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $folder);
        $this->folderService->delete($folder);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Folder deleted.']);
        }

        return back()->with('status', 'Folder deleted.');
    }
}
