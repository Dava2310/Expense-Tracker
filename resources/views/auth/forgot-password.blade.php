<x-layout.auth title="Forgot your password?" subtitle="Enter your email and we’ll send you a link to choose a new one.">
    <x-ui.status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf

        <x-forms.field label="Email" name="email" type="email" placeholder="you@example.com" required autofocus autocomplete="username" />

        <x-ui.button as="button" size="form" class="mt-2">Send reset link</x-ui.button>
    </form>

    <x-slot:footer>
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-saldo-ink">Back to log in</a>
    </x-slot:footer>
</x-layout.auth>
