<a {{ $attributes->merge(['class' => '-ml-1.5 inline-flex h-9 items-center gap-1 self-start rounded-[9px] pl-1.5 pr-3 text-sm font-medium text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover hover:text-saldo-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink']) }}>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"></path></svg>
    <span>{{ $slot }}</span>
</a>
