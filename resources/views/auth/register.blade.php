@php
    $emailTaken = $errors->has('email') && str_contains($errors->first('email'), 'already exists');
@endphp

<x-saldo.auth-layout title="Create your account" subtitle="Track your accounts, expenses and incomes in one place.">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <x-saldo.field label="Full name" name="name" placeholder="Alex Rivera" required autofocus autocomplete="name" />

        <x-saldo.field label="Email" name="email" type="email" placeholder="you@example.com" required autocomplete="username">
            @if ($emailTaken)
                <x-slot:errorAction>
                    <a href="{{ route('login') }}" class="font-semibold text-saldo-danger">Log in instead</a>
                </x-slot:errorAction>
            @endif
        </x-saldo.field>

        <x-saldo.field label="Password" name="password" type="password" placeholder="At least 8 characters" required autocomplete="new-password" />

        <x-saldo.field label="Confirm password" name="password_confirmation" type="password" placeholder="Repeat your password" required autocomplete="new-password" />

        <x-saldo.button as="button" size="form" class="mt-2">Create account</x-saldo.button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-saldo-ink">Log in</a>
    </x-slot:footer>
</x-saldo.auth-layout>
