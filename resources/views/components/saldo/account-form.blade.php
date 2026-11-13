@props(['action', 'cta', 'account' => null])

<form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
    @csrf
    @if ($account)
        @method('PATCH')
    @endif

    <div class="flex flex-col gap-5 p-6">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
            <x-saldo.field label="Account name" name="name" :value="$account?->name" placeholder="e.g. Everyday" required autofocus />
            <x-saldo.field label="Bank" name="bank" :value="$account?->bank" placeholder="e.g. Harbor Bank or Cash" required />
        </div>

        <x-saldo.field label="Details" name="details" type="textarea" :value="$account?->details" optional placeholder="What it’s for, card ending, anything useful" />

        <x-saldo.field
            label="Minimum balance"
            name="min_balance"
            prefix="€"
            inputmode="decimal"
            :value="$account?->min_balance"
            optional
            placeholder="0.00"
            hint="We’ll flag this account whenever its balance drops below this amount. Leave it empty for €0.00."
            class="max-w-[220px] tabular-nums"
        />

        @unless ($account)
            <p class="flex items-start gap-2.5 rounded-xl bg-saldo-canvas px-3.5 py-3 text-[13.5px] leading-normal text-saldo-muted">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-px flex-none"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8h.01"></path></svg>
                <span>New accounts start at €0.00. Add an income to set the opening balance.</span>
            </p>
        @endunless
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ route('accounts.index') }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-saldo.button as="button" class="px-4.5">{{ $cta }}</x-saldo.button>
    </div>
</form>
