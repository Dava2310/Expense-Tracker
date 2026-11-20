<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        This is a secure area of the application. Please confirm your password before continuing.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
        @csrf

        <x-saldo.field label="Password" name="password" type="password" required autocomplete="current-password" />

        <div class="flex justify-end">
            <x-saldo.button as="button">Confirm</x-saldo.button>
        </div>
    </form>
</x-guest-layout>
