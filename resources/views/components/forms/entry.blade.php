{{-- The form to register a new income or expense, depending on the kind --}}
@props(['kind', 'accounts', 'categories'])

@php
    $isIncome = $kind === 'income';
    $indexUrl = route($isIncome ? 'incomes.index' : 'expenses.index');

    // The symbol shown in the amount field follows the currency of the chosen account
    $symbols = $accounts->mapWithKeys(fn ($account): array => [$account->id => $account->currency->symbol]);
@endphp

<form
    method="POST"
    action="{{ route($isIncome ? 'incomes.store' : 'expenses.store') }}"
    x-data="{ symbols: @js($symbols), accountId: @js((string) old('account_id')), get symbol() { return this.symbols[this.accountId] ?? '' } }"
    class="overflow-hidden rounded-2xl border border-saldo-line bg-white"
>
    @csrf

    <div class="flex flex-col gap-5 p-6">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
            <x-forms.select :label="$isIncome ? 'Add to account' : 'Pay from account'" name="account_id" x-model="accountId" required autofocus>
                <option value="" disabled @selected(! old('account_id'))>Choose an account</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }} · {{ $account->formatMoney($account->balance) }}</option>
                @endforeach
            </x-forms.select>

            <x-forms.field label="Amount" name="amount" prefix-model="symbol" inputmode="decimal" placeholder="0.00" class="tabular-nums" required />
        </div>

        <x-forms.field label="Concept" name="concept" :placeholder="$isIncome ? 'e.g. Monthly salary' : 'e.g. Weekly groceries'" required />

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
            <x-forms.select label="Category" name="category_id" required>
                <option value="" disabled @selected(! old('category_id'))>Choose a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-forms.select>

            <x-forms.field label="Date" name="date" type="date" :value="now()->format('Y-m-d')" required />
        </div>
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ $indexUrl }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-ui.button as="button" class="px-4.5">{{ $isIncome ? 'Add income' : 'Add expense' }}</x-ui.button>
    </div>
</form>
