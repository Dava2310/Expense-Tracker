<x-saldo.auth-layout title="Verify your email" subtitle="Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn’t receive the email, we will gladly send you another.">
    @if (session('status') == 'verification-link-sent')
        <x-ui.status status="A new verification link has been sent to the email address you provided during registration." />
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="flex flex-col gap-4">
        @csrf

        <x-ui.button as="button" size="form">Resend verification email</x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center text-sm text-saldo-muted">
        @csrf

        <button type="submit" class="cursor-pointer font-semibold text-saldo-ink">Log out</button>
    </form>
</x-saldo.auth-layout>
