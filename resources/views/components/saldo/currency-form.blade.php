@props(['action', 'cta', 'currency' => null])

<form
    method="POST"
    action="{{ $action }}"
    x-data="{ symbol: @js(old('symbol', $currency?->symbol ?? '')) }"
    class="overflow-hidden rounded-2xl border border-saldo-line bg-white"
>
    @csrf
    @if ($currency)
        @method('PATCH')
    @endif

    <div class="flex flex-col gap-5 p-6">
        <div class="flex flex-wrap items-start gap-4">
            <div class="min-w-[260px] flex-[2_1_260px]">
                <x-saldo.field label="Name" name="name" :value="$currency?->name" placeholder="e.g. Euro" required autofocus />
            </div>

            <div class="max-w-[200px] min-w-[140px] flex-[1_1_140px]">
                <x-saldo.field label="Symbol" name="symbol" :value="$currency?->symbol" placeholder="e.g. €" maxlength="10" required x-model="symbol" class="font-display text-base font-semibold" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl bg-saldo-canvas px-4 py-3.5">
            <span class="text-[13.5px] text-saldo-muted">Amounts will look like</span>
            <span
                x-text="symbol.trim() ? symbol.trim() + '1,250.00' : '—'"
                x-bind:class="symbol.trim() ? 'text-saldo-ink' : 'text-saldo-subtle'"
                class="font-display text-xl font-medium tracking-[-0.01em]"
            ></span>
        </div>
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ route('currencies.index') }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-saldo.button as="button" class="px-4.5">{{ $cta }}</x-saldo.button>
    </div>
</form>
