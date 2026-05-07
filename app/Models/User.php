<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function outgoingShares(): HasMany
    {
        return $this->hasMany(FileShare::class, 'owner_id');
    }

    public function incomingShares(): HasMany
    {
        return $this->hasMany(FileShare::class, 'target_user_id');
    }

    public function publicLinks(): HasMany
    {
        return $this->hasMany(FilePublicLink::class, 'owner_id');
    }

    public function pluginRequests(): HasMany
    {
        return $this->hasMany(PluginRequest::class, 'requested_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
