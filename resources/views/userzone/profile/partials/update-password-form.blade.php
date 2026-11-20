<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Update Password
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Ensure your account is using a long, random password to stay secure.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <x-forms.field label="Current Password" name="current_password" id="update_password_current_password" type="password" bag="updatePassword" autocomplete="current-password" />

        <x-forms.field label="New Password" name="password" id="update_password_password" type="password" bag="updatePassword" autocomplete="new-password" />

        <x-forms.field label="Confirm Password" name="password_confirmation" id="update_password_password_confirmation" type="password" bag="updatePassword" autocomplete="new-password" />

        <div class="flex items-center gap-4">
            <x-saldo.button as="button">Save</x-saldo.button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Saved.</p>
            @endif
        </div>
    </form>
</section>
