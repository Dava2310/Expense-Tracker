@use('Illuminate\Support\Number')

<x-app-layout>
    <div class="mx-auto flex max-w-[1184px] flex-col gap-7 px-8 pb-18 pt-10">
        <x-saldo.page-header title="Accounts">
            <x-slot:subtitle>
                @if ($accounts->isEmpty())
                    Add your first account to start tracking your balance.
                @else
                    Total balance
                    <strong class="font-semibold tabular-nums text-saldo-ink">{{ Number::currency($accounts->sum(fn ($account): float => (float) $account->balance), 'EUR', 'en') }}</strong>
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

        @if (session('status') === 'account-created')
            <x-saldo.status status="Account created." />
        @endif
        @if (session('status') === 'account-updated')
            <x-saldo.status status="Account updated." />
        @endif

        <div class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-[15px]">
                    <thead>
                        <tr class="border-b border-saldo-line-soft text-[13px] font-medium text-saldo-subtle">
                            <th scope="col" class="px-6 py-3.5 font-medium">Account</th>
                            <th scope="col" class="hidden px-4 py-3.5 font-medium md:table-cell">Details</th>
                            <th scope="col" class="px-4 py-3.5 text-right font-medium">Balance</th>
                            <th scope="col" class="px-4 py-3.5 text-right font-medium">Minimum</th>
                            <th scope="col" class="px-4 py-3.5 font-medium">Status</th>
                            <th scope="col" class="px-6 py-3.5"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-t border-saldo-line-soft transition-colors first:border-t-0 hover:bg-saldo-canvas">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-medium">{{ $account->name }}</span>
                                        <span class="text-[13px] text-saldo-subtle">{{ $account->bank }}</span>
                                    </div>
                                </td>
                                <td class="hidden max-w-[260px] truncate px-4 py-4 text-[14px] text-saldo-muted md:table-cell">{{ filled($account->details) ? $account->details : '-' }}</td>
                                <td class="px-4 py-4 text-right font-medium tabular-nums">{{ Number::currency((float) $account->balance, 'EUR', 'en') }}</td>
                                <td class="px-4 py-4 text-right tabular-nums text-saldo-muted">{{ Number::currency((float) $account->min_balance, 'EUR', 'en') }}</td>
                                <td class="px-4 py-4">
                                    @if ($account->isBelowMinimum())
                                        <span class="inline-flex items-center rounded-full bg-saldo-warn-bg px-2.5 py-1 text-[12.5px] font-medium whitespace-nowrap text-saldo-warn-fg">Below minimum</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-saldo-green-tint px-2.5 py-1 text-[12.5px] font-medium whitespace-nowrap text-saldo-green">On track</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('accounts.edit', $account) }}" aria-label="Edit {{ $account->name }}" class="inline-flex h-9 items-center rounded-[9px] px-3 text-[13.5px] font-medium text-saldo-ink no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-saldo-muted">
                                    You have no accounts yet.
                                    <a href="{{ route('accounts.create') }}" class="font-medium text-saldo-ink underline">Create your first account</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
