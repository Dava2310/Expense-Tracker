@use('Illuminate\Support\Number')

<x-app-layout>
    <div class="mx-auto flex max-w-[1184px] flex-col gap-7 px-8 pb-18 pt-10">
        <x-saldo.page-header title="Dashboard">
            <x-slot:subtitle>
                @if ($accounts->isEmpty())
                    Add your first account to start tracking your balance.
                @else
                    Total balance
                    <strong class="font-semibold tabular-nums text-saldo-ink">{{ Number::currency($totalBalance, 'EUR', 'en') }}</strong>
                    across {{ $accounts->count() }} {{ str('account')->plural($accounts->count()) }}
                @endif
            </x-slot:subtitle>

            <x-slot:actions>
                <x-saldo.button :href="route('accounts.create')" class="gap-2 pl-3.5 pr-4.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    <span>New account</span>
                </x-saldo.button>
            </x-slot:actions>
        </x-saldo.page-header>

        @if ($accounts->isEmpty())
            <div class="flex flex-col items-start gap-4 rounded-2xl border border-dashed border-saldo-line-strong bg-white p-8">
                <p class="font-display text-xl font-semibold tracking-[-0.02em]">No accounts yet</p>
                <p class="max-w-md text-[15px] leading-normal text-saldo-muted">Add a bank account or your cash wallet and set a minimum balance. Saldo will flag it whenever it dips below.</p>
                <x-saldo.button :href="route('accounts.create')">Create your first account</x-saldo.button>
            </div>
        @else
            <ul class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-4">
                @foreach ($accounts as $account)
                    @php($isLow = $account->isBelowMinimum())
                    <li @class([
                        'flex min-h-[184px] flex-col gap-4.5 rounded-2xl border bg-white p-5',
                        'border-saldo-line' => ! $isLow,
                        'border-saldo-warn-line' => $isLow,
                    ])>
                        <div class="flex flex-col gap-0.5">
                            <span class="truncate text-[15px] font-semibold">{{ $account->name }}</span>
                            <span class="truncate text-[13px] text-saldo-subtle">{{ $account->bank }}</span>
                        </div>

                        <span class="mt-auto font-display text-[30px] font-medium tracking-[-0.02em] tabular-nums">{{ Number::currency((float) $account->balance, 'EUR', 'en') }}</span>

                        @if ($isLow)
                            <span class="inline-flex h-7 items-center gap-1.5 self-start rounded-full bg-saldo-warn-bg px-2.5 text-[12.5px] font-medium text-saldo-warn-fg">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                                <span>Below {{ Number::currency((float) $account->min_balance, 'EUR', 'en') }} minimum</span>
                            </span>
                        @else
                            <span class="flex h-7 items-center text-[13px] text-saldo-subtle">Minimum {{ Number::currency((float) $account->min_balance, 'EUR', 'en') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
