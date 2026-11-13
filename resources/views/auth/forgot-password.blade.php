<x-saldo.auth-layout title="Forgot your password?" subtitle="Enter your email and we’ll send you a link to choose a new one.">
    <x-saldo.status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf

        <x-saldo.field label="Email" name="email" type="email" placeholder="you@example.com" required autofocus autocomplete="username" />

        <x-saldo.button as="button" size="form" class="mt-2">Send reset link</x-saldo.button>
    </form>

    <x-slot:footer>
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-saldo-ink">Back to log in</a>
    </x-slot:footer>
</x-saldo.auth-layout>
