@props(['variant' => 'dark', 'size' => 'md', 'as' => 'a'])

@php
    $variantClasses = match ($variant) {
        'ghost' => 'bg-transparent text-saldo-ink hover:bg-saldo-ghost-hover',
        'light' => 'bg-white text-saldo-ink hover:bg-saldo-ghost-hover',
        'outline' => 'border border-saldo-line-strong bg-white text-saldo-ink hover:border-[#c9c8c1]',
        default => 'bg-saldo-ink text-white hover:bg-saldo-ink-hover',
    };

    $sizeClasses = match ($size) {
        'lg' => 'h-13 gap-2.5 rounded-xl px-6 text-base',
        'form' => 'h-12 w-full justify-center gap-2 rounded-[11px] px-6 text-[15px]',
        default => 'h-11 rounded-[11px] px-4 text-[14.5px]',
    };

    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center font-medium no-underline transition-colors duration-150 cursor-pointer',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink',
        $variantClasses,
        $sizeClasses,
    ]);
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@else
    <a {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@endif
