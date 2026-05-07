<?php

namespace App\Services;

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

    public function listTrash(User $user, int $perPage = 24): LengthAwarePaginator
    {
        $query = Folder::onlyTrashed()->latest('deleted_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($perPage)->withQueryString();
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
}
