<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
class FilePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, File $file): bool
    {
        if ($file->user_id === $user->id) {
            return true;
        }

        return $file->shares()->where('target_user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, File $file): bool
    {
        if ($file->user_id === $user->id) {
            return true;
        }

        return $file->shares()
            ->where('target_user_id', $user->id)
            ->where('permission', 'editor')
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }
}
