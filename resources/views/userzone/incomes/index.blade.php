@use('Illuminate\Support\Number')

<x-app-layout>
    <div class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-10">
        <x-saldo.page-header title="Incomes">
            <x-slot:subtitle>
                <strong class="font-semibold tabular-nums text-saldo-green">+{{ Number::currency($total, 'EUR', 'en') }}</strong>
                received {{ $periodPhrase }} {{ $scope }}
            </x-slot:subtitle>
        </x-saldo.page-header>

        <form
            method="GET"
            action="{{ route('incomes.index') }}"
            x-data="{ period: '{{ $period }}' }"
            class="flex flex-col gap-4 rounded-[14px] border border-saldo-line bg-white p-4"
        >
            <div class="flex flex-wrap items-end gap-x-4 gap-y-3">
                <x-saldo.select label="Period" name="period" id="filter-period" class="min-w-[220px]" x-model="period" x-on:change="period !== 'custom' && $el.form.requestSubmit()">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </x-saldo.select>

                <x-saldo.select label="Account" name="account" id="filter-account" class="min-w-[220px]" x-on:change="$el.form.requestSubmit()">
                    <option value="">All accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected($accountId === $account->id)>{{ $account->name }}</option>
                    @endforeach
                </x-saldo.select>

                <x-saldo.select label="Category" name="category" id="filter-category" class="min-w-[220px]" x-on:change="$el.form.requestSubmit()">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-saldo.select>

                @if ($activeFilters > 0)
                    <a href="{{ route('incomes.index') }}" class="inline-flex h-11 items-center rounded-[10px] px-3 text-sm font-medium text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Clear filters</a>
                @endif
            </div>

            <div
                x-show="period === 'custom'"
                @if ($period !== 'custom') style="display: none" @endif
                class="flex flex-wrap items-end gap-x-4 gap-y-3"
            >
                <x-saldo.field label="From" name="from" id="filter-from" type="date" :value="$from" class="min-w-[170px]" x-bind:disabled="period !== 'custom'" x-on:change="$el.form.requestSubmit()" />
                <x-saldo.field label="To" name="to" id="filter-to" type="date" :value="$to" class="min-w-[170px]" x-bind:disabled="period !== 'custom'" x-on:change="$el.form.requestSubmit()" />
            </div>

            <noscript>
                <x-saldo.button as="button" class="self-start">Apply</x-saldo.button>
            </noscript>
        </form>

        @foreach ($groups as $group)
            <section class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-saldo-line-soft bg-[#fafaf8] px-5 py-3.5">
                    <h2 class="text-sm font-semibold">{{ $group['title'] }}</h2>
                    <span class="text-[13.5px] tabular-nums text-saldo-muted">
                        {{ $group['count'] }} {{ str('income')->plural($group['count']) }} · +{{ Number::currency($group['total'], 'EUR', 'en') }}
                    </span>
                </div>

                <ul>
                    @foreach ($group['incomes'] as $income)
                        <li class="grid min-h-16 grid-cols-[88px_minmax(0,1fr)_auto] items-center gap-4 border-t border-saldo-line-soft px-5 py-2.5 first:border-t-0">
                            <span class="text-[13.5px] text-saldo-subtle">{{ $income->date->format('M j') }}</span>
                            <span class="flex min-w-0 flex-col gap-0.5">
                                <span class="truncate text-[15px] font-medium">{{ $income->concept }}</span>
                                <span class="text-[13px] text-saldo-subtle">{{ $income->category->name }} · {{ $income->account->name }}</span>
                            </span>
                            <span class="text-[15px] font-medium tabular-nums text-saldo-green">+{{ Number::currency((float) $income->amount, 'EUR', 'en') }}</span>
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
                    <x-saldo.button variant="ghost" :href="$showAllUrl" class="mt-2 border border-saldo-line-strong bg-white">Show all incomes</x-saldo.button>
                @elseif ($activeFilters > 0)
                    <p class="text-base font-semibold">No incomes match these filters</p>
                    <p class="text-[14.5px] text-saldo-muted">Try another account, category or period.</p>
                    <x-saldo.button variant="ghost" :href="route('incomes.index')" class="mt-2 border border-saldo-line-strong bg-white">Clear filters</x-saldo.button>
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
    </div>
</x-app-layout>
