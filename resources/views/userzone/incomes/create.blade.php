<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('incomes.index')">Incomes</x-saldo.back-link>

        <x-saldo.page-header title="New income">
            <x-slot:subtitle>The amount is added to the account you choose.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-saldo.income-form :accounts="$accounts" :categories="$categories" />
    </div>
</x-app-layout>
