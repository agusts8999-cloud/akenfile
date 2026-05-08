<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Models\File;
use App\Models\FilePublicLink;
use App\Models\FileShare;
use App\Models\Folder;
use App\Services\ControlCenterService;
use App\Services\FileService;
use App\Services\FolderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderService $folderService
    ) {
    }



    public function dashboard(Request $request): View
    {
        $this->authorize('viewAny', File::class);

        $user = $request->user();
        $fileQuery = File::query();
        $folderQuery = Folder::query()->whereNull('deleted_at');
        $trashFileQuery = File::onlyTrashed();
        $trashFolderQuery = Folder::onlyTrashed();
        $shareQuery = FileShare::query();
        $publicLinkQuery = FilePublicLink::query()->where('is_active', true);

        if (! $user->isAdmin()) {
            $fileQuery->where('user_id', $user->id);
            $folderQuery->where('user_id', $user->id);
            $trashFileQuery->where('user_id', $user->id);
            $trashFolderQuery->where('user_id', $user->id);
            $shareQuery->where('owner_id', $user->id);
            $publicLinkQuery->where('owner_id', $user->id);
        }

        $stats = [
            'total_files' => (int) $fileQuery->count(),
            'total_folders' => (int) $folderQuery->count(),
            'storage_used_bytes' => (int) $fileQuery->sum('size'),
            'trash_items' => (int) $trashFileQuery->count() + (int) $trashFolderQuery->count(),
            'active_shares' => (int) $shareQuery->count() + (int) $publicLinkQuery->count(),
        ];

        $recentFiles = File::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->latest('updated_at')
            ->take(6)
            ->get(['id', 'name', 'updated_at'])
            ->map(fn (File $file) => [
                'type' => 'file',
                'label' => $file->name,
                'action' => 'updated',
                'timestamp' => $file->updated_at,
            ]);

        $recentFolders = Folder::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->latest('updated_at')
            ->take(6)
            ->get(['id', 'name', 'updated_at'])
            ->map(fn (Folder $folder) => [
                'type' => 'folder',
                'label' => $folder->name,
                'action' => 'updated',
                'timestamp' => $folder->updated_at,
            ]);

        $recentTrashFiles = File::onlyTrashed()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->latest('deleted_at')
            ->take(6)
            ->get(['id', 'name', 'deleted_at'])
            ->map(fn (File $file) => [
                'type' => 'trash-file',
                'label' => $file->name,
                'action' => 'moved to trash',
                'timestamp' => $file->deleted_at,
            ]);

        $recentPublicLinks = FilePublicLink::query()
            ->with('file:id,name')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('owner_id', $user->id))
            ->latest('created_at')
            ->take(6)
            ->get(['id', 'file_id', 'created_at'])
            ->map(fn (FilePublicLink $link) => [
                'type' => 'share',
                'label' => $link->file?->name ?? 'Shared file',
                'action' => 'public link created',
                'timestamp' => $link->created_at,
            ]);

        $recentActivity = collect()
            ->merge($recentFiles)
            ->merge($recentFolders)
            ->merge($recentTrashFiles)
            ->merge($recentPublicLinks)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();


        $periodDays = in_array((int) $request->query('period', 7), [7, 30], true) ? (int) $request->query('period', 7) : 7;
        $startDate = Carbon::now()->subDays($periodDays - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $activityBuckets = [];
        for ($i = 0; $i < $periodDays; $i++) {
            $day = Carbon::now()->subDays(($periodDays - 1) - $i);
            $activityBuckets[$day->format('Y-m-d')] = [
                'date' => $day->toDateString(),
                'label' => $periodDays > 7 ? $day->format('d M') : $day->format('D'),
                'count' => 0,
            ];
        }

        $fileDaily = File::query()
            ->selectRaw('DATE(updated_at) as day, COUNT(*) as total')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupByRaw('DATE(updated_at)')
            ->get();

        $folderDaily = Folder::query()
            ->selectRaw('DATE(updated_at) as day, COUNT(*) as total')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupByRaw('DATE(updated_at)')
            ->get();

        $trashDaily = File::onlyTrashed()
            ->selectRaw('DATE(deleted_at) as day, COUNT(*) as total')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->whereBetween('deleted_at', [$startDate, $endDate])
            ->groupByRaw('DATE(deleted_at)')
            ->get();

        $shareDaily = FilePublicLink::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('owner_id', $user->id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->get();

        foreach ([$fileDaily, $folderDaily, $trashDaily, $shareDaily] as $dailySet) {
            foreach ($dailySet as $row) {
                $key = (string) $row->day;
                if (isset($activityBuckets[$key])) {
                    $activityBuckets[$key]['count'] += (int) $row->total;
                }
            }
        }

        $activityChart = array_values($activityBuckets);
        $weeklyTotal = array_sum(array_column($activityChart, 'count'));
        $peakCount = max(array_column($activityChart, 'count'));
        $peakDay = collect($activityChart)->firstWhere('count', $peakCount);

        $activityWeekly = [
            'total' => $weeklyTotal,
            'average' => round($weeklyTotal / $periodDays, 1),
            'peak' => $peakCount,
            'peak_label' => $peakDay['label'] ?? '-',
        ];

        return view('dashboard', compact('stats', 'recentActivity', 'activityChart', 'activityWeekly', 'periodDays'));
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
        $files->setCollection(
            $files->getCollection()->map(function (File $file) {
                $file->is_previewable_image = $this->fileService->isImageFile($file);
                $file->is_editable_text = $this->fileService->isEditableTextFile($file);
                $file->thumbnail_url = $file->is_previewable_image
                    ? route('files.preview.image', $file, false)
                    : null;

                return $file;
            })
        );
        $folders = $this->folderService->listForUser($request->user(), $folderId);
        $breadcrumbs = $currentFolder ? $this->folderService->breadcrumbs($currentFolder) : [];

        $allFoldersQuery = Folder::query()
            ->select(['id', 'name', 'parent_id', 'user_id'])
            ->whereNull('deleted_at')
            ->orderBy('name');

        if (! $request->user()->isAdmin()) {
            $allFoldersQuery->where('user_id', $request->user()->id);
        }

        $allFolders = $allFoldersQuery->get();
        $folderMap = $allFolders->keyBy('id');
        $labelCache = [];

        $resolveLabel = function (int $id) use (&$resolveLabel, &$labelCache, $folderMap): string {
            if (isset($labelCache[$id])) {
                return $labelCache[$id];
            }

            $folder = $folderMap->get($id);
            if (! $folder) {
                return 'Unknown folder';
            }

            if (! $folder->parent_id) {
                return $labelCache[$id] = $folder->name;
            }

            return $labelCache[$id] = $resolveLabel((int) $folder->parent_id).' / '.$folder->name;
        };

        $moveTargets = $allFolders
            ->map(fn (Folder $folder) => [
                'id' => $folder->id,
                'label' => $resolveLabel($folder->id),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $storageQuery = File::query();

        if (! $request->user()->isAdmin()) {
            $storageQuery->where('user_id', $request->user()->id);
        }

        $storageUsedBytes = (int) $storageQuery->sum('size');
        $previewDialogWidthPx = (int) app(ControlCenterService::class)->getSetting('preview_dialog_width_px', 500);
        $previewDialogWidthPx = max(200, min($previewDialogWidthPx, 1200));
        $fileThumbnailSizePx = (int) app(ControlCenterService::class)->getSetting('file_thumbnail_size_px', 48);
        $fileThumbnailSizePx = max(24, min($fileThumbnailSizePx, 160));

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

        return view('files.index', compact('files', 'folders', 'breadcrumbs', 'currentFolder', 'storageUsedBytes', 'moveTargets', 'previewDialogWidthPx', 'fileThumbnailSizePx'));
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

    public function preview(Request $request, File $file): JsonResponse
    {
        $this->authorize('view', $file);

        return response()->json([
            'data' => [
                'id' => $file->id,
                'name' => $file->name,
                'mime' => $file->mime,
                'is_image' => $this->fileService->isImageFile($file),
                'is_editable' => $this->fileService->isEditableTextFile($file),
                'is_previewable_image' => $this->fileService->isImageFile($file),
                'download_url' => route('files.download', $file, false),
                'preview_url' => $this->fileService->isImageFile($file) ? route('files.preview.image', $file, false) : null,
            ],
        ]);
    }

    public function previewImage(Request $request, File $file): StreamedResponse
    {
        $this->authorize('view', $file);
        abort_unless($this->fileService->isImageFile($file), 422, 'File is not an image.');

        return $this->fileService->streamImagePreview($file);
    }

    public function content(Request $request, File $file): JsonResponse
    {
        $this->authorize('view', $file);

        abort_unless($this->fileService->isEditableTextFile($file), 422, 'File is not editable.');

        return response()->json([
            'data' => [
                'id' => $file->id,
                'name' => $file->name,
                'mime' => $file->mime,
                'content' => $this->fileService->readTextContent($file),
            ],
        ]);
    }

    public function updateContent(Request $request, File $file): JsonResponse
    {
        $this->authorize('update', $file);

        abort_unless($this->fileService->isEditableTextFile($file), 422, 'File is not editable.');

        $payload = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $updated = $this->fileService->updateTextContent($file, (string) $payload['content']);

        return response()->json([
            'message' => 'File content updated.',
            'data' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'updated_at' => $updated->updated_at?->toISOString(),
            ],
        ]);
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

    public function bulkDelete(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'file_ids' => ['nullable', 'array'],
            'file_ids.*' => ['integer'],
            'folder_ids' => ['nullable', 'array'],
            'folder_ids.*' => ['integer'],
        ]);

        $fileIds = array_values(array_unique($payload['file_ids'] ?? []));
        $folderIds = array_values(array_unique($payload['folder_ids'] ?? []));

        $files = File::query()->whereIn('id', $fileIds)->get();
        foreach ($files as $file) {
            $this->authorize('delete', $file);
        }

        $folders = Folder::query()->whereIn('id', $folderIds)->get();
        foreach ($folders as $folder) {
            $this->authorize('delete', $folder);
        }

        $deletedFiles = $this->fileService->bulkDelete($fileIds);
        $deletedFolders = $this->folderService->bulkDelete($folderIds);

        return response()->json([
            'message' => 'Items deleted.',
            'data' => [
                'deleted_files' => $deletedFiles,
                'deleted_folders' => $deletedFolders,
            ],
        ]);
    }

    public function bulkMove(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'file_ids' => ['nullable', 'array'],
            'file_ids.*' => ['integer'],
            'folder_ids' => ['nullable', 'array'],
            'folder_ids.*' => ['integer'],
            'target_folder_id' => ['nullable', 'integer'],
        ]);

        $targetFolder = null;
        if (! empty($payload['target_folder_id'])) {
            $targetFolder = Folder::query()->findOrFail((int) $payload['target_folder_id']);
            $this->authorize('view', $targetFolder);
        }

        $fileIds = array_values(array_unique($payload['file_ids'] ?? []));
        $folderIds = array_values(array_unique($payload['folder_ids'] ?? []));

        $files = File::query()->whereIn('id', $fileIds)->get();
        foreach ($files as $file) {
            $this->authorize('update', $file);
        }

        $folders = Folder::query()->whereIn('id', $folderIds)->get();
        foreach ($folders as $folder) {
            $this->authorize('update', $folder);
        }

        $movedFiles = $this->fileService->bulkMove($fileIds, $targetFolder);
        $movedFolders = $this->folderService->bulkMove($folderIds, $targetFolder);

        return response()->json([
            'message' => 'Items moved.',
            'data' => [
                'moved_files' => $movedFiles,
                'moved_folders' => $movedFolders,
            ],
        ]);
    }

    public function bulkCopy(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'file_ids' => ['nullable', 'array'],
            'file_ids.*' => ['integer'],
            'folder_ids' => ['nullable', 'array'],
            'folder_ids.*' => ['integer'],
            'target_folder_id' => ['nullable', 'integer'],
        ]);

        $targetFolder = null;
        if (! empty($payload['target_folder_id'])) {
            $targetFolder = Folder::query()->findOrFail((int) $payload['target_folder_id']);
            $this->authorize('view', $targetFolder);
        }

        $fileIds = array_values(array_unique($payload['file_ids'] ?? []));
        $folderIds = array_values(array_unique($payload['folder_ids'] ?? []));

        $files = File::query()->whereIn('id', $fileIds)->get();
        foreach ($files as $file) {
            $this->authorize('view', $file);
        }

        $folders = Folder::query()->whereIn('id', $folderIds)->get();
        foreach ($folders as $folder) {
            $this->authorize('view', $folder);
        }

        $copiedFiles = $this->fileService->bulkCopy($request->user(), $fileIds, $targetFolder);
        $copiedFolders = $this->folderService->bulkCopy($request->user(), $folderIds, $targetFolder);

        return response()->json([
            'message' => 'Items copied.',
            'data' => [
                'copied_files' => $copiedFiles,
                'copied_folders' => $copiedFolders,
            ],
        ]);
    }
}
