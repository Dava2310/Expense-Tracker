@props(['categories', 'total', 'month'])

@use('Illuminate\Support\Number')

@php($top = $categories->max('amount'))

<section aria-labelledby="category-title" class="flex min-w-0 flex-col gap-4.5 rounded-2xl border border-saldo-line bg-white px-6 pb-5.5 pt-5">
    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
        <div class="flex flex-col gap-1">
            <h2 id="category-title" class="text-[15px] font-semibold">Spending by category</h2>
            <p class="text-[13.5px] text-saldo-subtle">{{ $month }} so far · {{ Number::currency($total, 'EUR', 'en') }} total</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="-mr-2 -mt-1.5 inline-flex h-9 items-center gap-0.5 rounded-[9px] pl-3 pr-1.5 text-[13.5px] font-medium text-saldo-ink no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
            <span>See expenses</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg>
        </a>
    </div>

    @if ($categories->isEmpty())
        <p class="py-10 text-center text-[14px] text-saldo-subtle">No expenses this month yet.</p>
    @else
        <ul class="flex flex-col gap-1.5">
            @foreach ($categories as $category)
                <li class="grid min-h-[30px] grid-cols-[108px_minmax(0,1fr)_84px] items-center gap-3">
                    <span class="truncate text-[13.5px]">{{ $category['name'] }}</span>
                    <span aria-hidden="true" class="flex h-3.5 items-center border-l border-[#c9c8c1]">
                        <span class="h-3.5 min-w-[3px] rounded-r bg-saldo-indigo" style="width: {{ round($category['amount'] / $top * 100, 1) }}%"></span>
                    </span>
                    <span class="text-right text-[13.5px] tabular-nums text-saldo-muted">{{ Number::currency($category['amount'], 'EUR', 'en') }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
