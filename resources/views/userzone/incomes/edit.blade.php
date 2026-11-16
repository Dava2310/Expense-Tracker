@use('Illuminate\Support\Number')

<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('incomes.index')">Incomes</x-saldo.back-link>

        <form method="POST" action="{{ route('incomes.update', $income) }}" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
            @csrf
            @method('PATCH')

            <div class="flex flex-col gap-3.5 border-b border-saldo-line-soft p-6">
                <span class="inline-flex h-[30px] items-center gap-1.5 self-start rounded-full bg-saldo-green-tint pl-[9px] pr-3 text-[13px] font-medium text-saldo-green">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 7 7 17"></path><path d="M16 17H7V8"></path></svg>
                    <span>Income</span>
                </span>

                <div class="flex flex-col gap-1.5">
                    <h1 class="font-display text-[40px] font-medium leading-[1.1] tracking-[-0.025em] tabular-nums text-saldo-green">+{{ Number::currency((float) $income->amount, 'EUR', 'en') }}</h1>
                    <p class="text-[14.5px] text-saldo-muted">Added to {{ $income->account->name }} on {{ $income->date->format('l, M j, Y') }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-6 p-6">
                <x-saldo.field label="Concept" name="concept" :value="$income->concept" required autofocus />

                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center gap-2 text-[13.5px] font-medium text-saldo-muted">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
                        <span>Locked details</span>
                    </div>

                    <dl class="overflow-hidden rounded-xl border border-saldo-line-soft bg-[#fafaf8]">
                        <div class="flex justify-between gap-4 px-4 py-3.5">
                            <dt class="text-sm text-saldo-subtle">Date</dt>
                            <dd class="text-right text-sm font-medium">{{ $income->date->format('M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-saldo-line-soft px-4 py-3.5">
                            <dt class="text-sm text-saldo-subtle">Category</dt>
                            <dd class="text-right text-sm font-medium">{{ $income->category->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-saldo-line-soft px-4 py-3.5">
                            <dt class="text-sm text-saldo-subtle">Account</dt>
                            <dd class="text-right text-sm font-medium">{{ $income->account->name }} · {{ $income->account->bank }}</dd>
                        </div>
                    </dl>

                    <p class="text-[13px] leading-normal text-saldo-subtle">Amount, date, category and account can’t change once saved, so your balances always add up. Only the concept is editable.</p>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
                <a href="{{ route('incomes.index') }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
                <x-saldo.button as="button" class="px-4.5">Save changes</x-saldo.button>
            </div>
        </form>
    </div>
</x-app-layout>
