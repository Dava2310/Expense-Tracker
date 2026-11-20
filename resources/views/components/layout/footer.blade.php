<footer class="mx-auto flex w-full max-w-[1184px] flex-wrap items-center justify-between gap-x-6 gap-y-4 px-8 pb-12 pt-8 text-[13.5px] text-saldo-subtle">
    <a href="{{ route('welcome') }}" aria-label="Saldo home" class="flex items-center gap-2 font-display text-[15px] font-semibold text-saldo-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
        <x-ui.logo :size="20" />
        <span>Saldo</span>
    </a>

    <nav aria-label="Footer" class="flex flex-wrap items-center gap-x-5 gap-y-2">
        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                @if (request()->routeIs($item['pattern'])) aria-current="page" @endif
                class="text-saldo-muted no-underline transition-colors hover:text-saldo-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink"
            >{{ $item['label'] }}</a>
        @endforeach
    </nav>

    <span>© {{ now()->year }} Saldo</span>
</footer>
