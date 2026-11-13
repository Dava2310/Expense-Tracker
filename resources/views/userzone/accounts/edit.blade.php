<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('accounts.show', $account)">{{ $account->name }}</x-saldo.back-link>

        <x-saldo.page-header title="Edit account">
            <x-slot:subtitle>Changes apply right away. Past expenses and incomes stay as they are.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-saldo.account-form :action="route('accounts.update', $account)" :account="$account" cta="Save changes" />
    </div>
</x-app-layout>
