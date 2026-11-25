<x-layout.app title="Edit income">
    {{-- "entry" holds the income for the delete dialog below --}}
    <div
        x-data="{ entry: @js([
            'amount' => $income->account->formatMoney($income->amount),
            'date' => $income->date->format('M j, Y'),
            'concept' => $income->concept,
            'account' => $income->account->name,
            'action' => route('incomes.destroy', $income),
        ]) }"
        class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7"
    >
        <x-ui.back-link :href="route('incomes.index')">Incomes</x-ui.back-link>

        <x-ui.page-header title="Edit income">
            <x-slot:subtitle>Changing the amount or the account updates the balances for you.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.entry kind="income" :entry="$income" :accounts="$accounts" :categories="$categories">
            <x-slot:delete>
                <x-ui.button as="button" type="button" variant="outline" class="text-saldo-danger" aria-haspopup="dialog" x-on:click="$dispatch('open-modal', 'confirm-entry-deletion')">Delete</x-ui.button>
            </x-slot:delete>
        </x-forms.entry>

        <x-ui.entry-delete-modal kind="income" />
    </div>
</x-layout.app>
