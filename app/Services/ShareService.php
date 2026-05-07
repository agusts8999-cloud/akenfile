<?php

namespace App\Services;

use App\Models\File;
use App\Models\FilePublicLink;
use App\Models\FileShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShareService
{
    public function shareToUser(File $file, User $owner, int $targetUserId, string $permission = 'viewer'): FileShare
    {
        return FileShare::query()->updateOrCreate(
            ['file_id' => $file->id, 'target_user_id' => $targetUserId],
            ['owner_id' => $owner->id, 'permission' => $permission]
        );
    }

    public function revokeUserShare(File $file, int $targetUserId): void
    {
        FileShare::query()->where('file_id', $file->id)->where('target_user_id', $targetUserId)->delete();
    }

    public function createPublicLink(File $file, User $owner, ?string $password = null, ?string $expiresAt = null): FilePublicLink
    {
        return FilePublicLink::query()->create([
            'file_id' => $file->id,
            'owner_id' => $owner->id,
            'token' => Str::random(48),
            'expires_at' => $expiresAt,
            'password' => $password ? Hash::make($password) : null,
            'is_active' => true,
        ]);
    }

    public function revokePublicLink(FilePublicLink $link): FilePublicLink
    {
        $link->update(['is_active' => false]);

        return $link->refresh();
    }

    public function linksForOwner(User $owner): Collection
    {
        return FilePublicLink::query()->with('file')->where('owner_id', $owner->id)->latest()->get();
    }

    public function resolvePublicLink(string $token): ?FilePublicLink
    {
        $link = FilePublicLink::query()->with('file')->where('token', $token)->first();

        if (! $link || ! $link->is_active) {
            return null;
        }

        if ($link->expires_at && now()->greaterThan($link->expires_at)) {
            return null;
        }

        return $link;
    }
}
