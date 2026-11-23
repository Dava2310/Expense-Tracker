@use('Illuminate\Support\Number')

<x-layout.app :title="$currency->name">
    <div class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('currencies.index')">Currencies</x-ui.back-link>

        @if (session('status') === 'currency-updated')
            <x-ui.status status="Currency updated." />
        @endif

        <div class="-mt-2 flex flex-wrap items-center justify-between gap-x-6 gap-y-4">
            <div class="flex items-center gap-4">
                <span aria-hidden="true" class="flex size-14 items-center justify-center rounded-[14px] border border-saldo-line bg-white font-display text-[26px] font-semibold">{{ $currency->symbol }}</span>
                <h1 class="font-display text-[32px] font-semibold leading-[1.1] tracking-[-0.025em]">{{ $currency->name }}</h1>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="outline" :href="route('currencies.edit', $currency)" class="gap-2 pl-3.5 pr-4">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                    <span>Edit</span>
                </x-ui.button>

                <x-ui.button as="button" type="button" variant="outline" class="gap-2 pl-3.5 pr-4" x-data="" :disabled="$isInUse" :title="$isInUse ? 'In use by '.$accountsCount.' '.str('account')->plural($accountsCount).', so it can’t be deleted' : null" :class="$isInUse ? 'cursor-not-allowed opacity-50 hover:border-saldo-line-strong' : ''" aria-haspopup="dialog" x-on:click.prevent="$dispatch('open-modal', 'confirm-currency-deletion')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
                    <span>Delete</span>
                </x-ui.button>
            </div>
        </div>

        <section aria-label="Currency details">
            <dl class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-px overflow-hidden rounded-2xl border border-saldo-line bg-saldo-line">
                <div class="flex flex-col gap-1.5 bg-white px-6 py-5">
                    <dt class="text-[13.5px] text-saldo-subtle">Name</dt>
                    <dd class="text-lg font-medium">{{ $currency->name }}</dd>
                </div>
                <div class="flex flex-col gap-1.5 bg-white px-6 py-5">
                    <dt class="text-[13.5px] text-saldo-subtle">Symbol</dt>
                    <dd class="font-display text-xl font-semibold">{{ $currency->symbol }}</dd>
                </div>
                <div class="flex flex-col gap-1.5 bg-white px-6 py-5">
                    <dt class="text-[13.5px] text-saldo-subtle">Accounts</dt>
                    <dd class="text-lg font-medium">
                        {{ $accountsCount }}
                        @if ($accountsCount > 0)
                            <span class="text-sm font-normal text-saldo-subtle">· {{ $activeAccountsCount }} active, {{ $inactiveAccountsCount }} inactive</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="currency-accounts-heading" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-saldo-line-soft px-5 py-4">
                <h2 id="currency-accounts-heading" class="text-[15px] font-semibold">Accounts in {{ $currency->name }}</h2>
                <span class="text-[13.5px] text-saldo-subtle">Total balance <strong class="font-semibold tabular-nums text-saldo-ink">{{ $totalBalance }}</strong></span>
            </div>

            @if ($accounts->isEmpty())
                <p class="px-5 py-10 text-center text-[14.5px] text-saldo-muted">No accounts use this currency yet.</p>
            @else
                <ul>
                    @foreach ($accounts as $account)
                        <li class="border-t border-saldo-line-soft first:border-t-0">
                            <a href="{{ route('accounts.show', $account) }}" @class([
                                'grid min-h-16 grid-cols-[minmax(0,1fr)_auto_auto_18px] items-center gap-4 px-5 py-2.5 no-underline transition-colors hover:bg-[#f7f7f4] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-saldo-ink',
                                'text-saldo-ink' => ! $account->trashed(),
                                'text-saldo-muted' => $account->trashed(),
                            ])>
                                <span class="flex min-w-0 flex-col gap-0.5">
                                    <span class="truncate text-[15px] font-medium">{{ $account->name }}</span>
                                    <span class="truncate text-[13px] text-saldo-subtle">{{ $account->bank }}</span>
                                </span>

                                <span>
                                    @if ($account->isBelowMinimum())
                                        <span class="inline-flex h-[26px] items-center gap-1.5 rounded-full bg-saldo-warn-bg px-2.5 text-[12.5px] font-medium text-saldo-warn-fg">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                                            <span>Below minimum</span>
                                        </span>
                                    @endif
                                    @if ($account->trashed())
                                        <span class="inline-flex h-[26px] items-center rounded-full bg-[#eaeae6] px-2.5 text-[12.5px] font-medium text-saldo-muted">Inactive</span>
                                    @endif
                                </span>

                                <span class="text-[15px] font-medium tabular-nums">{{ $currency->symbol }}{{ Number::format($account->balance, 2, locale: 'en') }}</span>

                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="text-saldo-subtle"><path d="m9 6 6 6-6 6"></path></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <x-ui.modal name="confirm-currency-deletion" labelledby="delete-title" describedby="delete-text">
        <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-[#fdecea] text-saldo-danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
        </span>

        <div class="flex flex-col gap-2">
            <h2 id="delete-title" class="font-display text-[22px] font-semibold tracking-[-0.02em]">Delete {{ $currency->name }} ({{ $currency->symbol }})?</h2>
            <p id="delete-text" class="text-[15px] leading-[1.55] text-saldo-muted">No accounts use it, so nothing else changes. This can’t be undone.</p>
        </div>

        <form method="POST" action="{{ route('currencies.destroy', $currency) }}" class="flex flex-wrap justify-end gap-2 pt-1">
            @csrf
            @method('DELETE')

            <x-ui.button as="button" type="button" variant="outline" x-on:click="$dispatch('close')">Cancel</x-ui.button>
            <x-ui.button as="button" variant="danger" class="px-4.5">Delete currency</x-ui.button>
        </form>
    </x-ui.modal>
</x-layout.app>
