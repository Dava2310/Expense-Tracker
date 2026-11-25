<x-layout.app title="New transfer">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('accounts.index')">Accounts</x-ui.back-link>

        <x-ui.page-header title="New transfer">
            <x-slot:subtitle>Move money between two of your accounts. It is not an income or an expense.</x-slot:subtitle>
        </x-ui.page-header>

        @if ($accounts->count() < 2)
            <div class="flex flex-col items-start gap-4 rounded-2xl border border-saldo-line bg-white p-6">
                <p class="text-[15px] text-saldo-muted">
                    @if ($accounts->isEmpty())
                        A transfer moves money between two of your accounts, and you have none yet.
                    @else
                        A transfer moves money between two of your accounts, and you only have {{ $accounts->first()->name }}. Add a second account to move money between them.
                    @endif
                </p>
                <x-ui.button :href="route('accounts.create')">Create an account</x-ui.button>
            </div>
        @else
            <x-forms.transfer :accounts="$accounts" />
        @endif
    </div>
</x-layout.app>
