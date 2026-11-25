{{-- The form to move money between two of the user's accounts, or to edit a transfer when one is given --}}
@props(['accounts', 'transfer' => null])

@php
    $selectedFrom = (string) old('from_account_id', $transfer?->from_account_id);
    $selectedTo = (string) old('to_account_id', $transfer?->to_account_id);

    // The symbol shown in the amount field follows the currency of the source account
    $symbols = $accounts->mapWithKeys(fn ($account): array => [$account->id => $account->currency->symbol]);
@endphp

<form
    method="POST"
    action="{{ $transfer ? route('transfers.update', $transfer) : route('transfers.store') }}"
    x-data="{ symbols: @js($symbols), fromId: @js($selectedFrom), get symbol() { return this.symbols[this.fromId] ?? '' } }"
    class="overflow-hidden rounded-2xl border border-saldo-line bg-white"
>
    @csrf
    @if ($transfer)
        @method('PATCH')
    @endif

    <div class="flex flex-col gap-5 p-6">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
            <x-forms.select label="From account" name="from_account_id" x-model="fromId" required autofocus>
                <option value="" disabled @selected($selectedFrom === '')>Choose an account</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($selectedFrom === (string) $account->id)>{{ $account->name }} · {{ $account->formatMoney($account->balance) }}</option>
                @endforeach
            </x-forms.select>

            <x-forms.select label="To account" name="to_account_id" required>
                <option value="" disabled @selected($selectedTo === '')>Choose an account</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($selectedTo === (string) $account->id)>{{ $account->name }} · {{ $account->formatMoney($account->balance) }}</option>
                @endforeach
            </x-forms.select>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
            <x-forms.field label="Amount" name="amount" :value="$transfer ? number_format($transfer->amount, 2, '.', '') : null" prefix-model="symbol" inputmode="decimal" placeholder="0.00" class="tabular-nums" required />

            <x-forms.field label="Date" name="date" type="date" :value="$transfer?->date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
        </div>

        <x-forms.field label="Note (optional)" name="note" :value="$transfer?->note" placeholder="e.g. Paying the credit card" />
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ route('accounts.index') }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-ui.button as="button" class="px-4.5">{{ $transfer ? 'Save changes' : 'Transfer' }}</x-ui.button>
    </div>
</form>
