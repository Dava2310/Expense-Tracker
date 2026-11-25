<x-layout.app title="Incomes">
    {{-- "entry" holds the row whose delete button was clicked, for the dialog at the end of the page --}}
    <div x-data="{ entry: {} }" class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-10">
        <x-ui.page-header title="Incomes">
            <x-slot:subtitle>
                Received {{ $periodPhrase }} {{ $scope }}
            </x-slot:subtitle>

            <x-slot:actions>
                <x-ui.button :href="route('incomes.create')" class="gap-2 pl-3.5 pr-4.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    <span>New income</span>
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status') === 'income-created')
            <x-ui.status status="Income created." />
        @endif
        @if (session('status') === 'income-updated')
            <x-ui.status status="Income updated." />
        @endif
        @if (session('status') === 'income-deleted')
            <x-ui.status status="Income deleted." />
        @endif
        @error('income')
            <p role="alert" class="rounded-[10px] border border-saldo-danger bg-saldo-danger-bg px-3.5 py-3 text-sm font-medium leading-normal text-saldo-danger">{{ $message }}</p>
        @enderror

        <x-forms.entry-filters
            :action="route('incomes.index')"
            :periods="$periods"
            :period="$period"
            :from="$from"
            :to="$to"
            :accounts="$accounts"
            :account-id="$accountId"
            :categories="$categories"
            :category-id="$categoryId"
            :active-filters="$activeFilters"
        />

        @foreach ($groups as $group)
            <section class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-saldo-line-soft bg-[#fafaf8] px-5 py-3.5">
                    <h2 class="text-sm font-semibold">{{ $group['title'] }}</h2>
                    <span class="text-[13.5px] tabular-nums text-saldo-muted">
                        {{ $group['count'] }} {{ str('income')->plural($group['count']) }}
                    </span>
                </div>

                <ul>
                    @foreach ($group['incomes'] as $income)
                        @php
                            $selection = [
                                'amount' => $income->account->formatMoney($income->amount),
                                'date' => $income->date->format('M j, Y'),
                                'concept' => $income->concept,
                                'account' => $income->account->name,
                                'action' => route('incomes.destroy', $income),
                            ];
                        @endphp
                        <li class="grid min-h-16 grid-cols-[88px_minmax(0,1fr)_auto_auto] items-center gap-4 border-t border-saldo-line-soft px-5 py-2.5 first:border-t-0">
                            <span class="text-[13.5px] text-saldo-subtle">{{ $income->date->format('M j') }}</span>
                            <span class="flex min-w-0 flex-col gap-0.5">
                                <a href="{{ route('incomes.edit', $income) }}" class="truncate text-[15px] font-medium text-saldo-ink no-underline hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">{{ $income->concept }}</a>
                                <span class="text-[13px] text-saldo-subtle">{{ $income->category->name }} · {{ $income->account->name }}</span>
                            </span>
                            <span class="text-[15px] font-medium tabular-nums text-saldo-green">+{{ $income->account->formatMoney($income->amount) }}</span>
                            <span class="flex justify-end gap-1">
                                <a href="{{ route('incomes.edit', $income) }}" aria-label="Edit {{ $income->concept }}" class="flex size-10 items-center justify-center rounded-[10px] text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                                </a>

                                <button type="button" aria-label="Delete {{ $income->concept }}" aria-haspopup="dialog" x-on:click="entry = @js($selection); $dispatch('open-modal', 'confirm-entry-deletion')" class="flex size-10 cursor-pointer items-center justify-center rounded-[10px] text-saldo-muted transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
                                </button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        @php
            $showAllUrl = route('incomes.index', array_merge(request()->except(['period', 'from', 'to']), ['period' => 'all']));
        @endphp

        @if ($groups->isEmpty())
            <section class="flex flex-col items-center gap-2.5 rounded-2xl border border-dashed border-saldo-line-strong px-6 py-14 text-center">
                @if ($outsideCount > 0)
                    <p class="text-base font-semibold">No incomes in this period</p>
                    <p class="text-[14.5px] text-saldo-muted">There {{ $outsideCount === 1 ? 'is 1 income' : 'are '.$outsideCount.' incomes' }} outside it.</p>
                    <x-ui.button variant="ghost" :href="$showAllUrl" class="mt-2 border border-saldo-line-strong bg-white">Show all incomes</x-ui.button>
                @elseif ($activeFilters > 0)
                    <p class="text-base font-semibold">No incomes match these filters</p>
                    <p class="text-[14.5px] text-saldo-muted">Try another account, category or period.</p>
                    <x-ui.button variant="ghost" :href="route('incomes.index')" class="mt-2 border border-saldo-line-strong bg-white">Clear filters</x-ui.button>
                @else
                    <p class="text-base font-semibold">No incomes yet</p>
                    <p class="text-[14.5px] text-saldo-muted">Money you receive will show up here, grouped by month.</p>
                @endif
            </section>
        @elseif ($outsideCount > 0)
            <div class="flex justify-center">
                <a href="{{ $showAllUrl }}" class="inline-flex h-11 items-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Show all incomes ({{ $outsideCount }} more)</a>
            </div>
        @endif

        <x-ui.entry-delete-modal kind="income" />
    </div>
</x-layout.app>
