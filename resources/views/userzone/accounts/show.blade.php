@use('Illuminate\Support\Number')

<x-app-layout>
    <div class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('accounts.index')">Accounts</x-saldo.back-link>

        @if (session('status') === 'account-updated')
            <x-saldo.status status="Account updated." />
        @endif

        <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
            <div class="flex max-w-[640px] flex-col gap-2.5">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-[32px] font-semibold leading-[1.1] tracking-[-0.025em]">{{ $account->name }}</h1>
                    <span class="inline-flex h-7 items-center rounded-full bg-[#eaeae6] px-2.5 text-[13px] font-medium text-saldo-muted">{{ $account->bank }}</span>
                </div>
                @if (filled($account->details))
                    <p class="text-[15px] leading-[1.55] text-saldo-muted">{{ $account->details }}</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <x-saldo.button variant="outline" :href="route('accounts.edit', $account)" class="gap-2 pl-3.5 pr-4">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                    <span>Edit</span>
                </x-saldo.button>

                <x-saldo.button as="button" type="button" variant="outline" class="gap-2 pl-3.5 pr-4" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-account-deactivation')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="5" rx="1"></rect><path d="M5 9v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9"></path><path d="M10 13h4"></path></svg>
                    <span>Deactivate</span>
                </x-saldo.button>
            </div>
        </div>

        <section aria-label="Summary" class="grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] gap-px overflow-hidden rounded-2xl border border-saldo-line bg-saldo-line">
            <div class="flex flex-col gap-1.5 bg-white px-6 py-5">
                <span class="text-[13.5px] text-saldo-subtle">Balance</span>
                <span class="font-display text-[34px] font-medium tracking-[-0.025em] tabular-nums">{{ Number::currency((float) $account->balance, 'EUR', 'en') }}</span>
            </div>

            <div class="flex flex-col gap-1.5 bg-white px-6 py-5">
                <span class="text-[13.5px] text-saldo-subtle">Minimum balance</span>
                <span class="font-display text-2xl font-medium tracking-[-0.02em] tabular-nums">{{ Number::currency((float) $account->min_balance, 'EUR', 'en') }}</span>
                @if ($account->isBelowMinimum())
                    <span class="inline-flex items-center gap-1.5 text-[13px] text-saldo-warn-fg">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                        <span>{{ Number::currency(abs($aboveMinimum), 'EUR', 'en') }} below minimum</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-[13px] text-saldo-green">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"></path></svg>
                        <span>{{ Number::currency($aboveMinimum, 'EUR', 'en') }} above minimum</span>
                    </span>
                @endif
            </div>

            <div class="flex flex-col gap-2.5 bg-white px-6 py-5">
                <span class="text-[13.5px] text-saldo-subtle">This month</span>
                <div class="flex justify-between gap-3 text-[15px]">
                    <span class="text-saldo-muted">In</span>
                    <span class="font-medium tabular-nums text-saldo-green">+{{ Number::currency($monthIncome, 'EUR', 'en') }}</span>
                </div>
                <div class="flex justify-between gap-3 text-[15px]">
                    <span class="text-saldo-muted">Out</span>
                    <span class="font-medium tabular-nums">−{{ Number::currency($monthExpenses, 'EUR', 'en') }}</span>
                </div>
            </div>
        </section>

        <section aria-labelledby="activity-heading" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-saldo-line-soft py-3 pl-5 pr-3">
                <h2 id="activity-heading" class="text-[15px] font-semibold">Activity</h2>
                <a href="{{ route('incomes.index', ['account' => $account->id]) }}" class="inline-flex h-10 items-center rounded-[10px] px-3.5 text-sm font-medium text-saldo-ink no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">View all incomes</a>
            </div>

            @if ($activity->isEmpty())
                <p class="px-5 py-10 text-center text-[14.5px] text-saldo-muted">No activity yet. Incomes and expenses of this account will show up here.</p>
            @else
                <ul>
                    @foreach ($activity as $entry)
                        @php($isIncome = $entry['type'] === 'income')
                        <li class="grid min-h-16 grid-cols-[40px_minmax(0,1fr)_auto] items-center gap-3.5 border-t border-saldo-line-soft px-5 py-2.5 first:border-t-0">
                            <span aria-hidden="true" @class([
                                'flex size-9 items-center justify-center rounded-[10px]',
                                'bg-saldo-green-tint text-saldo-green' => $isIncome,
                                'bg-[#f0f0ec] text-saldo-muted' => ! $isIncome,
                            ])>
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    @if ($isIncome)
                                        <path d="M17 7 7 17"></path><path d="M16 17H7V8"></path>
                                    @else
                                        <path d="M7 17 17 7"></path><path d="M8 7h9v9"></path>
                                    @endif
                                </svg>
                            </span>
                            <span class="flex min-w-0 flex-col gap-0.5">
                                <span class="truncate text-[15px] font-medium">{{ $entry['concept'] }}</span>
                                <span class="text-[13px] text-saldo-subtle">{{ $entry['date']->format('M j') }} · {{ $entry['category'] }}</span>
                            </span>
                            <span @class([
                                'text-[15px] font-medium tabular-nums',
                                'text-saldo-green' => $isIncome,
                            ])>{{ $isIncome ? '+' : '−' }}{{ Number::currency($entry['amount'], 'EUR', 'en') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <x-saldo.modal name="confirm-account-deactivation" labelledby="deactivate-title" describedby="deactivate-text">
        <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-[#f0f0ec] text-saldo-ink">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="5" rx="1"></rect><path d="M5 9v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9"></path><path d="M10 13h4"></path></svg>
        </span>

        <div class="flex flex-col gap-2">
            <h2 id="deactivate-title" class="font-display text-[22px] font-semibold tracking-[-0.02em]">Deactivate {{ $account->name }}?</h2>
            <p id="deactivate-text" class="text-[15px] leading-[1.55] text-saldo-muted">It will be hidden from your accounts and won’t be offered for new expenses or incomes. Its history stays exactly as it is.</p>
        </div>

        <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="flex flex-wrap justify-end gap-2 pt-1">
            @csrf
            @method('DELETE')

            <x-saldo.button as="button" type="button" variant="outline" x-on:click="$dispatch('close')">Cancel</x-saldo.button>
            <x-saldo.button as="button" class="px-4.5">Deactivate account</x-saldo.button>
        </form>
    </x-saldo.modal>
</x-app-layout>
