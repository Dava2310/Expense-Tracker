@php
    $fromAccount = $accounts->firstWhere('id', $transfer->from_account_id);
    $toAccount = $accounts->firstWhere('id', $transfer->to_account_id);
@endphp

<x-layout.app title="Edit transfer">
    {{-- "entry" holds the transfer for the delete dialog below --}}
    <div
        x-data="{ entry: @js([
            'amount' => $fromAccount->formatMoney($transfer->amount),
            'date' => $transfer->date->format('M j, Y'),
            'concept' => $fromAccount->name.' to '.$toAccount->name,
            'action' => route('transfers.destroy', $transfer),
        ]) }"
        class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7"
    >
        <x-ui.back-link :href="route('accounts.index')">Accounts</x-ui.back-link>

        <x-ui.page-header title="Edit transfer">
            <x-slot:subtitle>Changing the amount or the accounts updates the balances for you.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.transfer :transfer="$transfer" :accounts="$accounts">
            <x-slot:delete>
                <x-ui.button as="button" type="button" variant="outline" class="text-saldo-danger" aria-haspopup="dialog" x-on:click="$dispatch('open-modal', 'confirm-entry-deletion')">Delete</x-ui.button>
            </x-slot:delete>
        </x-forms.transfer>

        <x-ui.entry-delete-modal kind="transfer" />
    </div>
</x-layout.app>
