<x-layout.app title="Edit expense">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('expenses.index')">Expenses</x-ui.back-link>

        <x-ui.page-header title="Edit expense">
            <x-slot:subtitle>Changing the amount or the account updates the balances for you.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.entry kind="expense" :entry="$expense" :accounts="$accounts" :categories="$categories" />
    </div>
</x-layout.app>
