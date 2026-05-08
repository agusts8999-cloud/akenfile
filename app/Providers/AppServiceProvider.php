<?php

namespace App\Providers;

use App\Models\File;
use App\Models\Folder;
use App\Policies\FilePolicy;
use App\Policies\FolderPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(File::class, FilePolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);

        RateLimiter::for('login', function (Request $request): array {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by(strtolower((string) $request->input('email'))),
            ];
        });
    }
}
