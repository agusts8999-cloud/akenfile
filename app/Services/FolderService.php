<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FolderService
{
    public function listForUser(User $user, ?int $parentId): Collection
    {
        $query = Folder::query()->withCount(['children', 'files'])->orderBy('name')->whereNull('deleted_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->get();
    }

    public function create(User $user, string $name, ?Folder $parent = null): Folder
    {
        return Folder::query()->create([
            'name' => trim($name),
            'parent_id' => $parent?->id,
            'user_id' => $user->id,
        ]);
    }

    public function rename(Folder $folder, string $name): Folder
    {
        $folder->update(['name' => trim($name)]);

        return $folder->refresh();
    }

    public function move(Folder $folder, ?Folder $targetFolder): Folder
    {
        if ($targetFolder && $this->isDescendant($targetFolder, $folder)) {
            return $folder;
        }

        $folder->update(['parent_id' => $targetFolder?->id]);

        return $folder->refresh();
    }

    public function bulkDelete(array $folderIds): int
    {
        $folders = Folder::query()->whereIn('id', $folderIds)->get();

        foreach ($folders as $folder) {
            $this->delete($folder);
        }

        return $folders->count();
    }

    public function bulkMove(array $folderIds, ?Folder $targetFolder): int
    {
        $folders = Folder::query()->whereIn('id', $folderIds)->get();
        $moved = 0;

        foreach ($folders as $folder) {
            if ($targetFolder && $this->isDescendant($targetFolder, $folder)) {
                continue;
            }

            $folder->update(['parent_id' => $targetFolder?->id]);
            $moved++;
        }

        return $moved;
    }

    public function bulkCopy(User $user, array $folderIds, ?Folder $targetFolder): int
    {
        $folders = Folder::query()->whereIn('id', $folderIds)->get();
        $copied = 0;

        DB::transaction(function () use ($folders, $user, $targetFolder, &$copied): void {
            foreach ($folders as $folder) {
                $this->copyRecursive($folder, $user, $targetFolder);
                $copied++;
            }
        });

        return $copied;
    }

    public function delete(Folder $folder): void
    {
        DB::transaction(function () use ($folder): void {
            foreach ($folder->children as $child) {
                $this->delete($child);
            }

            foreach ($folder->files as $file) {
                app(FileService::class)->delete($file);
            }

            $folder->delete();
        });
    }

    public function breadcrumbs(Folder $folder): array
    {
        $breadcrumbs = [];
        $node = $folder;

        while ($node) {
            $breadcrumbs[] = $node;
            $node = $node->parent;
        }

        return array_reverse($breadcrumbs);
    }

    public function listTrash(User $user, ?int $perPage = null): LengthAwarePaginator
    {
        $query = Folder::onlyTrashed()->latest('deleted_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($this->resolvePerPage($perPage))->withQueryString();
    }

    public function restore(Folder $folder): Folder
    {
        $folder->restore();

        return $folder->refresh();
    }

    public function forceDelete(Folder $folder): void
    {
        $folder->forceDelete();
    }

    private function resolvePerPage(?int $perPage = null): int
    {
        if ($perPage !== null && $perPage >= 5) {
            return min($perPage, 200);
        }

        $configured = (int) app(ControlCenterService::class)->getSetting('rows_per_page', 24);

        return max(5, min($configured, 200));
    }

    private function copyRecursive(Folder $source, User $owner, ?Folder $targetParent): Folder
    {
        $newFolder = Folder::query()->create([
            'name' => $source->name.' (copy)',
            'parent_id' => $targetParent?->id,
            'user_id' => $owner->id,
        ]);

        foreach ($source->files as $file) {
            app(FileService::class)->bulkCopy($owner, [$file->id], $newFolder);
        }

        foreach ($source->children as $child) {
            $this->copyRecursive($child, $owner, $newFolder);
        }

        return $newFolder;
    }

    private function isDescendant(Folder $candidate, Folder $ancestor): bool
    {
        if ($candidate->id === $ancestor->id) {
            return true;
        }

        $node = $candidate->parent;
        while ($node) {
            if ($node->id === $ancestor->id) {
                return true;
            }

            $node = $node->parent;
        }

        return false;
    }
}
