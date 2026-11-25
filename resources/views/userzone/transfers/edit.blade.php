<x-layout.app title="Edit transfer">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('accounts.index')">Accounts</x-ui.back-link>

        <x-ui.page-header title="Edit transfer">
            <x-slot:subtitle>Changing the amount or the accounts updates the balances for you.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.transfer :transfer="$transfer" :accounts="$accounts" />
    </div>
</x-layout.app>
