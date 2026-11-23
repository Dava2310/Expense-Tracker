@props(['months', 'max', 'account' => null, 'accounts', 'preserve' => []])

@php
    $lastIndex = $months->count() - 1;
    $money = fn (float $amount, int $precision = 2): string => $account->formatMoney($amount, $precision);

    $readouts = $account === null ? collect() : $months->map(fn (array $month): array => [
        'name' => $month['name'],
        'income' => '+'.$money($month['income']),
        'expenses' => '−'.$money($month['expenses']),
        'net' => ($month['surplus'] ? '+' : '−').$money($month['net']),
    ])->values();

    $tickValues = [0, 0.25, 0.5, 0.75, 1];
@endphp

<section aria-labelledby="flow-title" class="flex min-w-0 flex-col gap-4.5 rounded-2xl border border-saldo-line bg-white px-6 pb-5.5 pt-5">
    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
        <div class="flex flex-col gap-1">
            <h2 id="flow-title" class="text-[15px] font-semibold">Income vs. expenses</h2>
            <p class="text-[13.5px] text-saldo-subtle">Last 6 months{{ $account ? ' · '.$account->name : '' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <div aria-hidden="true" class="flex h-[22px] items-center gap-4 text-[13px] text-saldo-muted">
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-[3px] bg-saldo-green"></span>Income</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-[3px] bg-saldo-indigo"></span>Expenses</span>
            </div>
            <x-forms.account-filter name="inc_exp" :accounts="$accounts" :selected="$account?->id" :preserve="$preserve" />
        </div>
    </div>

    @if (! $account)
        <p class="py-10 text-center text-[14px] text-saldo-subtle">Choose an account to see its income and expenses.</p>
    @elseif ($max <= 0)
        <p class="py-10 text-center text-[14px] text-saldo-subtle">No incomes or expenses in this period yet.</p>
    @else
        <div x-data="{ active: {{ $lastIndex }}, readouts: @js($readouts) }" class="flex flex-col gap-4.5">
            <div aria-live="polite" class="flex min-h-[22px] flex-wrap items-baseline gap-x-4 gap-y-1 text-[14px] text-saldo-muted">
                <span class="font-semibold text-saldo-ink" x-text="readouts[active].name"></span>
                <span>In <strong class="font-semibold tabular-nums text-saldo-ink" x-text="readouts[active].income"></strong></span>
                <span>Out <strong class="font-semibold tabular-nums text-saldo-ink" x-text="readouts[active].expenses"></strong></span>
                <span>Net <strong class="font-semibold tabular-nums text-saldo-ink" x-text="readouts[active].net"></strong></span>
            </div>

            <div class="grid grid-cols-[48px_minmax(0,1fr)] gap-2.5">
                <div aria-hidden="true" class="relative h-[180px]">
                    @foreach ($tickValues as $tick)
                        <span class="absolute right-0 translate-y-1/2 text-xs leading-none tabular-nums text-saldo-subtle" style="bottom: {{ $tick * 100 }}%">{{ $money($max * $tick, 0) }}</span>
                    @endforeach
                </div>

                <div class="flex min-w-0 flex-col gap-2">
                    <div class="relative h-[180px]">
                        @foreach ($tickValues as $tick)
                            <div aria-hidden="true" @class(['absolute inset-x-0 h-px', 'bg-saldo-line-soft' => $tick > 0, 'bg-[#c9c8c1]' => $tick === 0]) style="bottom: {{ $tick * 100 }}%"></div>
                        @endforeach

                        <div class="absolute inset-0 flex gap-1">
                            @foreach ($months as $index => $month)
                                <button
                                    type="button"
                                    @mouseenter="active = {{ $index }}"
                                    @focus="active = {{ $index }}"
                                    @mouseleave="active = {{ $lastIndex }}"
                                    @blur="active = {{ $lastIndex }}"
                                    :class="{ 'bg-[#f3f3f0]': active === {{ $index }} }"
                                    aria-label="{{ $month['name'] }}: income {{ $money($month['income']) }}, expenses {{ $money($month['expenses']) }}"
                                    class="flex h-full min-w-0 flex-1 cursor-default items-end justify-center gap-0.5 rounded-t-lg p-0 focus-visible:outline-2 focus-visible:outline-saldo-ink"
                                >
                                    <span class="w-[18px] rounded-t bg-saldo-green" style="height: {{ round($month['income'] / $max * 100, 2) }}%"></span>
                                    <span class="w-[18px] rounded-t bg-saldo-indigo" style="height: {{ round($month['expenses'] / $max * 100, 2) }}%"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div aria-hidden="true" class="flex gap-1">
                        @foreach ($months as $index => $month)
                            <span :class="active === {{ $index }} ? 'font-semibold text-saldo-ink' : 'text-saldo-subtle'" class="min-w-0 flex-1 text-center text-[12.5px]">{{ $month['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
