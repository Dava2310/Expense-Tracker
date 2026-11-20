@php
    $emailTaken = $errors->has('email') && str_contains($errors->first('email'), 'already exists');
@endphp

<x-saldo.auth-layout title="Create your account" subtitle="Track your accounts, expenses and incomes in one place.">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <x-forms.field label="Full name" name="name" placeholder="Alex Rivera" required autofocus autocomplete="name" />

        <x-forms.field label="Email" name="email" type="email" placeholder="you@example.com" required autocomplete="username">
            @if ($emailTaken)
                <x-slot:errorAction>
                    <a href="{{ route('login') }}" class="font-semibold text-saldo-danger">Log in instead</a>
                </x-slot:errorAction>
            @endif
        </x-forms.field>

        <x-forms.field label="Password" name="password" type="password" placeholder="At least 8 characters" required autocomplete="new-password" />

        <x-forms.field label="Confirm password" name="password_confirmation" type="password" placeholder="Repeat your password" required autocomplete="new-password" />

        <x-ui.button as="button" size="form" class="mt-2">Create account</x-ui.button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-saldo-ink">Log in</a>
    </x-slot:footer>
</x-saldo.auth-layout>
