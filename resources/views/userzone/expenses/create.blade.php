<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('expenses.index')">Expenses</x-saldo.back-link>

        <x-saldo.page-header title="New expense">
            <x-slot:subtitle>The amount is deducted from the account you choose.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-saldo.entry-form kind="expense" :accounts="$accounts" :categories="$categories" />
    </div>
</x-app-layout>
