<x-layout.app>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('expenses.index')">Expenses</x-ui.back-link>

        <x-ui.page-header title="New expense">
            <x-slot:subtitle>The amount is deducted from the account you choose.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.entry kind="expense" :accounts="$accounts" :categories="$categories" />
    </div>
</x-layout.app>
