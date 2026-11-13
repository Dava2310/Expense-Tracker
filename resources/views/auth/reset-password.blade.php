<x-saldo.auth-layout title="Choose a new password" subtitle="Pick a password you haven’t used before, at least 8 characters.">
    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-saldo.field label="Email" name="email" type="email" :value="$request->email" placeholder="you@example.com" required autofocus autocomplete="username" />

        <x-saldo.field label="New password" name="password" type="password" placeholder="At least 8 characters" required autocomplete="new-password" />

        <x-saldo.field label="Confirm new password" name="password_confirmation" type="password" placeholder="Repeat your password" required autocomplete="new-password" />

        <x-saldo.button as="button" size="form" class="mt-2">Reset password</x-saldo.button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="font-semibold text-saldo-ink">Back to log in</a>
    </x-slot:footer>
</x-saldo.auth-layout>
