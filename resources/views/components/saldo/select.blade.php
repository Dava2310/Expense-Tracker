@props(['label', 'name', 'id' => null])

@php
    $id ??= $name;
@endphp

<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-[13px] font-medium text-saldo-muted">{{ $label }}</label>

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            {{ $attributes->class('block h-11 w-full cursor-pointer appearance-none rounded-[10px] border border-saldo-line-strong bg-white bg-none py-0 pl-3.5 pr-10 text-[15px] text-saldo-ink shadow-none transition-colors hover:border-[#c9c8c1] focus:border-saldo-ink focus:ring-[3px] focus:ring-saldo-ink/10 focus:outline-none') }}
        >
            {{ $slot }}
        </select>

        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="pointer-events-none absolute right-3 top-[13px] text-saldo-subtle"><path d="m6 9 6 6 6-6"></path></svg>
    </div>
</div>
