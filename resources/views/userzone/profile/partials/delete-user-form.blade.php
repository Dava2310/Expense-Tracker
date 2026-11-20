<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Delete Account
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
        </p>
    </header>

    <x-saldo.button
        as="button"
        type="button"
        variant="danger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Delete Account</x-saldo.button>

    <x-saldo.modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" labelledby="confirm-user-deletion-title" describedby="confirm-user-deletion-description">
        <form method="post" action="{{ route('profile.destroy') }}" class="flex flex-col gap-5">
            @csrf
            @method('delete')

            <div class="flex flex-col gap-2">
                <h2 id="confirm-user-deletion-title" class="text-lg font-medium text-gray-900">
                    Are you sure you want to delete your account?
                </h2>

                <p id="confirm-user-deletion-description" class="text-sm text-gray-600">
                    Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                </p>
            </div>

            <x-forms.field label="Password" name="password" id="delete_user_password" type="password" bag="userDeletion" placeholder="Password" />

            <div class="flex justify-end gap-3">
                <x-saldo.button as="button" type="button" variant="outline" x-on:click="$dispatch('close')">
                    Cancel
                </x-saldo.button>

                <x-saldo.button as="button" variant="danger">
                    Delete Account
                </x-saldo.button>
            </div>
        </form>
    </x-saldo.modal>
</section>
