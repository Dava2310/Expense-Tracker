<x-saldo.auth-layout title="Welcome back" subtitle="Log in to see your balances.">
    <x-saldo.status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf

        <x-saldo.field label="Email" name="email" type="email" placeholder="you@example.com" required autofocus autocomplete="username" />

        <x-saldo.field label="Password" name="password" type="password" placeholder="Your password" required autocomplete="current-password">
            @if (Route::has('password.request'))
                <x-slot:aside>
                    <a href="{{ route('password.request') }}" class="rounded text-[13.5px] font-medium text-saldo-muted underline-offset-2 hover:text-saldo-ink hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                        Forgot password?
                    </a>
                </x-slot:aside>
            @endif
        </x-saldo.field>

        <label for="remember_me" class="inline-flex items-center gap-2.5 text-sm text-saldo-muted">
            <input id="remember_me" type="checkbox" name="remember" class="size-4 rounded border-saldo-line-strong text-saldo-ink focus:ring-saldo-ink">
            <span>Remember me</span>
        </label>

        <x-saldo.button as="button" size="form" class="mt-2">Log in</x-saldo.button>
    </form>

    <x-slot:footer>
        New to Saldo?
        <a href="{{ route('register') }}" class="font-semibold text-saldo-ink">Create an account</a>
    </x-slot:footer>
</x-saldo.auth-layout>
