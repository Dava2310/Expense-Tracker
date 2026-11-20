<x-saldo.auth-layout title="Confirm your password" subtitle="This is a secure area of the application. Please confirm your password before continuing.">
    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
        @csrf

        <x-forms.field label="Password" name="password" type="password" required autofocus autocomplete="current-password" />

        <x-ui.button as="button" size="form" class="mt-2">Confirm</x-ui.button>
    </form>
</x-saldo.auth-layout>
