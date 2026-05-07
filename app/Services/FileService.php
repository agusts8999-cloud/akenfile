<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileShare;
use App\Models\Folder;
use App\Models\User;
use App\Services\ControlCenterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{
    public function listForUser(User $user, ?int $folderId, ?string $search = null, int $perPage = 24): LengthAwarePaginator
    {
        $query = File::query()->with('folder')->latest()->whereNull('deleted_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function upload(User $user, UploadedFile $uploadedFile, ?Folder $folder = null): File
    {
        $this->enforceStorageQuota($user, $uploadedFile);

        return DB::transaction(function () use ($user, $uploadedFile, $folder): File {
            $safeName = $this->sanitizeFilename(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $uploadedFile->getClientOriginalExtension();
            $finalName = trim($safeName.'.'.$extension, '.');

            $folderSegment = $folder?->id ?? 'root';
            $directory = "uploads/{$user->id}/{$folderSegment}";
            $storedPath = $uploadedFile->storeAs($directory, Str::uuid().'-'.$finalName, 'public');

            return File::query()->create([
                'name' => $finalName,
                'path' => $storedPath,
                'size' => $uploadedFile->getSize() ?: 0,
                'mime' => $uploadedFile->getClientMimeType() ?: 'application/octet-stream',
                'user_id' => $user->id,
                'folder_id' => $folder?->id,
            ]);
        });
    }

    public function rename(File $file, string $newName): File
    {
        $safeBaseName = $this->sanitizeFilename(pathinfo($newName, PATHINFO_FILENAME));
        $extension = pathinfo($newName, PATHINFO_EXTENSION) ?: pathinfo($file->name, PATHINFO_EXTENSION);
        $file->update(['name' => trim($safeBaseName.'.'.$extension, '.')]);

        return $file->refresh();
    }

    public function move(File $file, ?Folder $targetFolder): File
    {
        $file->update(['folder_id' => $targetFolder?->id]);

        return $file->refresh();
    }

    public function delete(File $file): void
    {
        $file->delete();
    }

    public function forceDelete(File $file): void
    {
        Storage::disk('public')->delete($file->path);
        $file->forceDelete();
    }

    public function restore(File $file): File
    {
        $file->restore();

        return $file->refresh();
    }

    public function listTrash(User $user, int $perPage = 24): LengthAwarePaginator
    {
        $query = File::onlyTrashed()->with('folder')->latest('deleted_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function listSharedWith(User $user): Collection
    {
        return File::query()
            ->whereHas('shares', function ($query) use ($user): void {
                $query->where('target_user_id', $user->id);
            })
            ->with(['user', 'folder'])
            ->latest()
            ->get();
    }

    public function listOwnedShares(User $user): Collection
    {
        return FileShare::query()
            ->with(['file', 'targetUser'])
            ->where('owner_id', $user->id)
            ->latest()
            ->get();
    }

    public function download(File $file): StreamedResponse
    {
        return Storage::disk('public')->download($file->path, $file->name);
    }

    private function sanitizeFilename(string $name): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $name) ?: 'file';

        return trim($sanitized, '._-') ?: 'file';
    }

    private function enforceStorageQuota(User $user, UploadedFile $uploadedFile): void
    {
        $settings = app(ControlCenterService::class);
        $storageLimitGb = (int) $settings->getSetting('storage_limit_gb', 10);
        $storageLimitBytes = max(1, $storageLimitGb) * 1024 * 1024 * 1024;

        $usedBytes = (int) File::query()->where('user_id', $user->id)->sum('size');
        $incomingBytes = (int) ($uploadedFile->getSize() ?: 0);

        if (($usedBytes + $incomingBytes) > $storageLimitBytes) {
            throw ValidationException::withMessages([
                'file' => ['Storage quota exceeded. Increase storage space in Control Center or delete unused files.'],
            ]);
        }
    }
}
