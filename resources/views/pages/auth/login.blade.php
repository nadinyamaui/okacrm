<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Use your email and password or continue with your social account.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <label for="remember" class="inline-flex min-h-11 items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <input id="remember" type="checkbox" name="remember" class="rounded border-zinc-300 text-zinc-900 shadow-sm focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-900" />
                <span>{{ __('Remember me') }}</span>
            </label>

            <flux:button type="submit" variant="primary" class="w-full" data-test="email-login-button">
                {{ __('Log in') }}
            </flux:button>

            <div class="text-right text-sm">
                <flux:link :href="route('password.request')" wire:navigate>{{ __('Forgot your password?') }}</flux:link>
            </div>
        </form>

        <div class="relative">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white px-2 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ __('Or continue with') }}</span>
            </div>
        </div>

        @if ($errors->has('oauth'))
            <div class="rounded-xl border border-red-300/70 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('oauth') }}
            </div>
        @endif

        <div class="flex flex-col gap-3">
            @foreach (\App\Enums\SocialNetwork::cases() as $network)
                @if (in_array($network, [\App\Enums\SocialNetwork::Instagram, \App\Enums\SocialNetwork::Tiktok], true))
                    <flux:button :href="route('social.auth', ['provider' => $network])" variant="primary" class="w-full" data-test="{{ $network->value }}-login-button">
                        {{ __('Continue with :network', ['network' => $network->label()]) }}
                    </flux:button>
                @else
                    <flux:button type="button" variant="primary" class="w-full" :disabled="true" data-test="{{ $network->value }}-login-button">
                        {{ __('Continue with :network (Coming soon)', ['network' => $network->label()]) }}
                    </flux:button>
                @endif
            @endforeach
        </div>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Need an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Create one') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
