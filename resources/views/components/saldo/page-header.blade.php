@props(['title'])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="flex flex-col gap-1.5">
        <h1 class="font-display text-[32px] font-semibold leading-[1.1] tracking-[-0.025em]">{{ $title }}</h1>
        @isset($subtitle)
            <p class="text-[15px] text-saldo-muted">{{ $subtitle }}</p>
        @endisset
    </div>

    @isset($actions)
        {{ $actions }}
    @endisset
</div>
