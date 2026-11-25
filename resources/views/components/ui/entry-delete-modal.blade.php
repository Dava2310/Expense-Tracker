{{-- Asks for confirmation before an income, an expense or a transfer is deleted.
     The page keeps the chosen entry in Alpine as: entry = { amount, date, concept, account, action } --}}
@props(['kind'])

@php($isIncome = $kind === 'income')

<x-ui.modal name="confirm-entry-deletion" labelledby="delete-entry-title" describedby="delete-entry-text">
    <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-[#fdecea] text-saldo-danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
    </span>

    <div class="flex flex-col gap-2">
        <h2 id="delete-entry-title" class="font-display text-[22px] font-semibold tracking-[-0.02em]">Delete this {{ $kind }}?</h2>
        <p id="delete-entry-text" class="text-[15px] leading-[1.55] text-saldo-muted">
            <strong class="font-semibold text-saldo-ink" x-text="entry.amount"></strong> on <span x-text="entry.date"></span> · “<span x-text="entry.concept"></span>”.
            @if ($kind === 'transfer')
                Both balances go back to what they were. This can’t be undone.
            @else
                {{ $isIncome ? 'The amount is taken out of' : 'The amount goes back to' }} <span x-text="entry.account"></span>. This can’t be undone.
            @endif
        </p>
    </div>

    <form method="POST" x-bind:action="entry.action" class="flex flex-wrap justify-end gap-2 pt-1">
        @csrf
        @method('DELETE')

        <x-ui.button as="button" type="button" variant="outline" x-on:click="$dispatch('close')">Cancel</x-ui.button>
        <x-ui.button as="button" variant="danger" class="px-4.5">Delete {{ $kind }}</x-ui.button>
    </form>
</x-ui.modal>
