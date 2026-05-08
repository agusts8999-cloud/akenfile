<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileShare;
use App\Models\Folder;
use App\Models\User;
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
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    private const EDITABLE_MIME_TYPES = [
        'text/plain',
        'text/html',
        'text/markdown',
    ];

    private const EDITABLE_EXTENSIONS = [
        'txt', 'md', 'markdown', 'html', 'htm',
    ];

    public function listForUser(User $user, ?int $folderId, ?string $search = null, ?int $perPage = null): LengthAwarePaginator
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

        return $query->paginate($this->resolvePerPage($perPage))->withQueryString();
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

    public function bulkDelete(array $fileIds): int
    {
        $files = File::query()->whereIn('id', $fileIds)->get();

        foreach ($files as $file) {
            $this->delete($file);
        }

        return $files->count();
    }

    public function bulkMove(array $fileIds, ?Folder $targetFolder): int
    {
        return File::query()
            ->whereIn('id', $fileIds)
            ->update(['folder_id' => $targetFolder?->id]);
    }

    public function bulkCopy(User $user, array $fileIds, ?Folder $targetFolder): int
    {
        $files = File::query()->whereIn('id', $fileIds)->get();
        $copied = 0;

        DB::transaction(function () use ($files, $user, $targetFolder, &$copied): void {
            foreach ($files as $file) {
                $this->copySingleFile($file, $user, $targetFolder);
                $copied++;
            }
        });

        return $copied;
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

    public function listTrash(User $user, ?int $perPage = null): LengthAwarePaginator
    {
        $query = File::onlyTrashed()->with('folder')->latest('deleted_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($this->resolvePerPage($perPage))->withQueryString();
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

    public function isImageFile(File $file): bool
    {
        $mime = strtolower((string) $file->mime);
        $extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

        if (Str::startsWith($mime, 'image/')) {
            return true;
        }

        return $extension !== '' && in_array($extension, self::IMAGE_EXTENSIONS, true);
    }

    public function isEditableTextFile(File $file): bool
    {
        $mime = strtolower((string) $file->mime);
        $extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

        if (in_array($mime, self::EDITABLE_MIME_TYPES, true)) {
            return true;
        }

        if ($extension !== '' && in_array($extension, self::EDITABLE_EXTENSIONS, true)) {
            return true;
        }

        return false;
    }

    public function readTextContent(File $file): string
    {
        $this->ensureEditableAndSafeSize($file);

        return (string) Storage::disk('public')->get($file->path);
    }

    public function updateTextContent(File $file, string $content): File
    {
        $this->ensureEditableAndSafeSize($file);

        Storage::disk('public')->put($file->path, $content);
        $file->update(['size' => strlen($content)]);

        return $file->refresh();
    }

    public function streamImagePreview(File $file): StreamedResponse
    {
        $mime = $this->resolveMimeType($file);

        return Storage::disk('public')->response($file->path, $file->name, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function copySingleFile(File $sourceFile, User $owner, ?Folder $targetFolder): File
    {
        $folderSegment = $targetFolder?->id ?? 'root';
        $targetDirectory = "uploads/{$owner->id}/{$folderSegment}";
        $targetName = Str::uuid().'-'.$sourceFile->name;
        $targetPath = $targetDirectory.'/'.$targetName;

        Storage::disk('public')->copy($sourceFile->path, $targetPath);

        return File::query()->create([
            'name' => $this->generateCopyName($sourceFile->name),
            'path' => $targetPath,
            'size' => $sourceFile->size,
            'mime' => $sourceFile->mime,
            'user_id' => $owner->id,
            'folder_id' => $targetFolder?->id,
        ]);
    }

    private function generateCopyName(string $name): string
    {
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $suffix = ' (copy)';

        if ($extension === '') {
            return $baseName.$suffix;
        }

        return $baseName.$suffix.'.'.$extension;
    }

    private function resolvePerPage(?int $perPage = null): int
    {
        if ($perPage !== null && $perPage >= 5) {
            return min($perPage, 200);
        }

        $configured = (int) app(ControlCenterService::class)->getSetting('rows_per_page', 24);

        return max(5, min($configured, 200));
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

    private function ensureEditableAndSafeSize(File $file): void
    {
        if (! $this->isEditableTextFile($file)) {
            throw ValidationException::withMessages([
                'file' => ['File type is not supported for text editing.'],
            ]);
        }

        // Prevent loading very large files into TinyMCE.
        if ($file->size > (2 * 1024 * 1024)) {
            throw ValidationException::withMessages([
                'file' => ['File is too large to edit in browser (max 2MB).'],
            ]);
        }
    }

    private function resolveMimeType(File $file): string
    {
        $mime = strtolower((string) $file->mime);
        if (Str::startsWith($mime, 'image/')) {
            return $mime;
        }

        $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
