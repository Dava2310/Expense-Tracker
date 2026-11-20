{{-- One income or expense, where only the concept can be changed --}}
@props(['entry', 'kind'])

@php
    $isIncome = $kind === 'income';
    $indexUrl = route($isIncome ? 'incomes.index' : 'expenses.index');
@endphp

<form method="POST" action="{{ route($isIncome ? 'incomes.update' : 'expenses.update', $entry) }}" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
    @csrf
    @method('PATCH')

    <div class="flex flex-col gap-3.5 border-b border-saldo-line-soft p-6">
        <span @class([
            'inline-flex h-[30px] items-center gap-1.5 self-start rounded-full pl-[9px] pr-3 text-[13px] font-medium',
            'bg-saldo-green-tint text-saldo-green' => $isIncome,
            'bg-[#f0f0ec] text-saldo-ink' => ! $isIncome,
        ])>
            @if ($isIncome)
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 7 7 17"></path><path d="M16 17H7V8"></path></svg>
            @else
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"></path><path d="M8 7h9v9"></path></svg>
            @endif
            <span>{{ $isIncome ? 'Income' : 'Expense' }}</span>
        </span>

        <div class="flex flex-col gap-1.5">
            <h1 @class([
                'font-display text-[40px] font-medium leading-[1.1] tracking-[-0.025em] tabular-nums',
                'text-saldo-green' => $isIncome,
            ])>{{ $isIncome ? '+' : '−' }}{{ $entry->account->formatMoney($entry->amount) }}</h1>
            <p class="text-[14.5px] text-saldo-muted">{{ $isIncome ? 'Added to' : 'Deducted from' }} {{ $entry->account->name }} on {{ $entry->date->format('l, M j, Y') }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-6 p-6">
        <x-forms.field label="Concept" name="concept" :value="$entry->concept" required autofocus />

        <div class="flex flex-col gap-2.5">
            <div class="flex items-center gap-2 text-[13.5px] font-medium text-saldo-muted">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
                <span>Locked details</span>
            </div>

            <dl class="overflow-hidden rounded-xl border border-saldo-line-soft bg-[#fafaf8]">
                <div class="flex justify-between gap-4 px-4 py-3.5">
                    <dt class="text-sm text-saldo-subtle">Date</dt>
                    <dd class="text-right text-sm font-medium">{{ $entry->date->format('M j, Y') }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-saldo-line-soft px-4 py-3.5">
                    <dt class="text-sm text-saldo-subtle">Category</dt>
                    <dd class="text-right text-sm font-medium">{{ $entry->category->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-saldo-line-soft px-4 py-3.5">
                    <dt class="text-sm text-saldo-subtle">Account</dt>
                    <dd class="text-right text-sm font-medium">{{ $entry->account->name }} · {{ $entry->account->bank }}</dd>
                </div>
            </dl>

            <p class="text-[13px] leading-normal text-saldo-subtle">Amount, date, category and account can’t change once saved, so your balances always add up. Only the concept is editable.</p>
        </div>
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ $indexUrl }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-saldo.button as="button" class="px-4.5">Save changes</x-saldo.button>
    </div>
</form>
