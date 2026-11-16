@use('Illuminate\Support\Number')

{{-- The form to register a new income or expense, depending on the kind --}}
@props(['kind', 'accounts', 'categories'])

@php
    $isIncome = $kind === 'income';
    $indexUrl = route($isIncome ? 'incomes.index' : 'expenses.index');
@endphp

<form method="POST" action="{{ route($isIncome ? 'incomes.store' : 'expenses.store') }}" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
    @csrf

    <div class="flex flex-col gap-5 p-6">
        <x-saldo.field label="Amount" name="amount" prefix="€" inputmode="decimal" placeholder="0.00" class="max-w-[220px] tabular-nums" required autofocus />

        <x-saldo.field label="Concept" name="concept" :placeholder="$isIncome ? 'e.g. Monthly salary' : 'e.g. Weekly groceries'" required />

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
            <x-saldo.select label="Category" name="category_id" required>
                <option value="" disabled @selected(! old('category_id'))>Choose a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-saldo.select>

            <x-saldo.field label="Date" name="date" type="date" :value="now()->format('Y-m-d')" required />
        </div>

        <x-saldo.select :label="$isIncome ? 'Add to account' : 'Pay from account'" name="account_id" required>
            <option value="" disabled @selected(! old('account_id'))>Choose an account</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }} · {{ Number::currency((float) $account->balance, 'EUR', 'en') }}</option>
            @endforeach
        </x-saldo.select>
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ $indexUrl }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-saldo.button as="button" class="px-4.5">{{ $isIncome ? 'Add income' : 'Add expense' }}</x-saldo.button>
    </div>
</form>
