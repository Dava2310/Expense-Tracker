<x-app-layout>
    <div class="mx-auto flex max-w-[1184px] flex-col gap-7 px-8 pb-18 pt-10">
        <x-ui.page-header title="Dashboard">
            <x-slot:subtitle>
                @if ($accounts->isEmpty())
                    Add your first account to start tracking your balance.
                @else
                    {{ $accounts->count() }} {{ str('account')->plural($accounts->count()) }}
                @endif
            </x-slot:subtitle>

            <x-slot:actions>
                <x-ui.button :href="route('accounts.create')" class="gap-2 pl-3.5 pr-4.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    <span>New account</span>
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($accounts->isEmpty())
            <div class="flex flex-col items-start gap-4 rounded-2xl border border-dashed border-saldo-line-strong bg-white p-8">
                <p class="font-display text-xl font-semibold tracking-[-0.02em]">No accounts yet</p>
                <p class="max-w-md text-[15px] leading-normal text-saldo-muted">Add a bank account or your cash wallet and set a minimum balance. Saldo will flag it whenever it dips below.</p>
                <x-ui.button :href="route('accounts.create')">Create your first account</x-ui.button>
            </div>
        @else
            <ul class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-4">
                @foreach ($accounts as $account)
                    @php($isLow = $account->isBelowMinimum())
                    <li>
                        <a href="{{ route('accounts.show', $account) }}" @class([
                            'flex min-h-[184px] flex-col gap-4.5 rounded-2xl border bg-white p-5 text-saldo-ink no-underline transition hover:border-[#cfcec8] hover:shadow-[0_10px_28px_-12px_rgba(22,24,27,0.16)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink',
                            'border-saldo-line' => ! $isLow,
                            'border-saldo-warn-line' => $isLow,
                        ])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-col gap-0.5">
                                <span class="truncate text-[15px] font-semibold">{{ $account->name }}</span>
                                <span class="truncate text-[13px] text-saldo-subtle">{{ $account->bank }}</span>
                            </div>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-none text-saldo-subtle"><path d="m9 6 6 6-6 6"></path></svg>
                        </div>

                        <span class="mt-auto font-display text-[30px] font-medium tracking-[-0.02em] tabular-nums">{{ $account->formatMoney($account->balance) }}</span>

                        @if ($isLow)
                            <span class="inline-flex h-7 items-center gap-1.5 self-start rounded-full bg-saldo-warn-bg px-2.5 text-[12.5px] font-medium text-saldo-warn-fg">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                                <span>Below {{ $account->formatMoney($account->min_balance) }} minimum</span>
                            </span>
                        @else
                            <span class="flex h-7 items-center text-[13px] text-saldo-subtle">Minimum {{ $account->formatMoney($account->min_balance) }}</span>
                        @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(460px,100%),1fr))] gap-4">
                <x-ui.flow-chart :months="$monthlyFlow" :max="$chartMax" :account="$flowAccount" :accounts="$accounts" :preserve="['cat' => $categoryAccount?->id]" />
                <x-ui.category-spending :categories="$categorySpending" :total="$categoryTotal" :month="$currentMonth" :account="$categoryAccount" :accounts="$accounts" :preserve="['inc_exp' => $flowAccount?->id]" />
            </div>
        @endif

        @if ($inactiveAccounts->isNotEmpty())
            <section aria-labelledby="inactive-heading" class="flex flex-col gap-3 pt-3">
                <h2 id="inactive-heading" class="flex items-center gap-2 text-sm font-semibold text-saldo-muted">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="5" rx="1"></rect><path d="M5 9v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9"></path><path d="M10 13h4"></path></svg>
                    <span>Inactive</span>
                    <span class="inline-flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-saldo-line px-1.5 text-xs text-saldo-ink">{{ $inactiveAccounts->count() }}</span>
                </h2>
                <ul class="grid grid-cols-[repeat(auto-fill,minmax(min(240px,100%),1fr))] gap-4">
                    @foreach ($inactiveAccounts as $account)
                        <li class="flex flex-col gap-3.5 rounded-2xl border border-dashed border-[#d6d5cf] p-5 text-saldo-muted">
                            <div class="flex flex-col gap-0.5">
                                <span class="truncate text-[15px] font-semibold">{{ $account->name }}</span>
                                <span class="truncate text-[13px] text-saldo-subtle">{{ $account->bank }}</span>
                            </div>
                            <span class="font-display text-[22px] font-medium tabular-nums">{{ $account->formatMoney($account->balance) }}</span>
                            <span class="text-[13px] text-saldo-subtle">Deactivated {{ $account->deleted_at->format('M j, Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-app-layout>
