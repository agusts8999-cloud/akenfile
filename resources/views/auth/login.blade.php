<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <div x-data="{show:false}" class="mt-1 w-full" style="position: relative;">
                <x-text-input id="password" class="block w-full pr-11"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password" />
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

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
