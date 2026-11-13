@props(['href' => null, 'active' => false])

@php
    $baseClasses = 'flex h-10 items-center gap-2 whitespace-nowrap rounded-[9px] px-3.5 text-sm font-medium';
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        @if ($active) aria-current="page" @endif
        {{ $attributes->class([
            $baseClasses,
            'no-underline transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink',
            'bg-white text-saldo-ink shadow-[0_1px_2px_rgba(22,24,27,0.08),0_0_0_1px_rgba(22,24,27,0.04)]' => $active,
            'text-saldo-muted hover:text-saldo-ink' => ! $active,
        ]) }}
    >
        {{ $icon ?? '' }}
        <span>{{ $slot }}</span>
    </a>
@else
    <span aria-disabled="true" {{ $attributes->class([$baseClasses, 'cursor-not-allowed text-saldo-muted/50']) }}>
        {{ $icon ?? '' }}
        <span>{{ $slot }}</span>
    </span>
@endif
