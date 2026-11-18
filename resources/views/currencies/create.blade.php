<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('currencies.index')">Currencies</x-saldo.back-link>

        <x-saldo.page-header title="New currency">
            <x-slot:subtitle>Add a currency so you can open accounts in it.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-saldo.currency-form :action="route('currencies.store')" cta="Create currency" />
    </div>
</x-app-layout>
