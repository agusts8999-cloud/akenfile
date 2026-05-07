<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div x-data="{show:false}" class="mt-1 w-full" style="position: relative;">
                <x-text-input id="update_password_current_password" name="current_password" x-bind:type="show ? 'text' : 'password'" class="block w-full pr-11" autocomplete="current-password" />
                <button type="button" @click="show = !show" class="z-10 text-gray-500 hover:text-gray-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                    <span class="sr-only">Toggle password visibility</span>
                    <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                        <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                    </svg>
                    <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div x-data="{show:false}" class="mt-1 w-full" style="position: relative;">
                <x-text-input id="update_password_password" name="password" x-bind:type="show ? 'text' : 'password'" class="block w-full pr-11" autocomplete="new-password" />
                <button type="button" @click="show = !show" class="z-10 text-gray-500 hover:text-gray-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                    <span class="sr-only">Toggle password visibility</span>
                    <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                        <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                    </svg>
                    <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div x-data="{show:false}" class="mt-1 w-full" style="position: relative;">
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" x-bind:type="show ? 'text' : 'password'" class="block w-full pr-11" autocomplete="new-password" />
                <button type="button" @click="show = !show" class="z-10 text-gray-500 hover:text-gray-700" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;">
                    <span class="sr-only">Toggle password visibility</span>
                    <svg x-show="!show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                        <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                    </svg>
                    <svg x-show="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.7 10.7A3 3 0 0013.3 13.3M9.88 5.09A10.9 10.9 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.72 18.72 0 01-4.08 4.88M6.61 6.61A18.82 18.82 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 005.39-1.52"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
