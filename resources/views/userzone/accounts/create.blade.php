<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('accounts.index')">Accounts</x-saldo.back-link>

        <x-saldo.page-header title="New account">
            <x-slot:subtitle>A bank account, a savings pot or the cash in your wallet.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-saldo.account-form :action="route('accounts.store')" cta="Create account" />
    </div>
</x-app-layout>
