<x-layout.app title="Edit expense">
    {{-- "entry" holds the expense for the delete dialog below --}}
    <div
        x-data="{ entry: @js([
            'amount' => $expense->account->formatMoney($expense->amount),
            'date' => $expense->date->format('M j, Y'),
            'concept' => $expense->concept,
            'account' => $expense->account->name,
            'action' => route('expenses.destroy', $expense),
        ]) }"
        class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7"
    >
        <x-ui.back-link :href="route('expenses.index')">Expenses</x-ui.back-link>

        <x-ui.page-header title="Edit expense">
            <x-slot:subtitle>Changing the amount or the account updates the balances for you.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.entry kind="expense" :entry="$expense" :accounts="$accounts" :categories="$categories">
            <x-slot:delete>
                <x-ui.button as="button" type="button" variant="outline" class="text-saldo-danger" aria-haspopup="dialog" x-on:click="$dispatch('open-modal', 'confirm-entry-deletion')">Delete</x-ui.button>
            </x-slot:delete>
        </x-forms.entry>

        <x-ui.entry-delete-modal kind="expense" />
    </div>
</x-layout.app>
