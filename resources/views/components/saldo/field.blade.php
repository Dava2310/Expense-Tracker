@props(['label', 'name', 'type' => 'text', 'id' => null, 'value' => null, 'hint' => null, 'optional' => false, 'prefix' => null])

@php
    $id ??= $name;
    $error = $errors->first($name);
    $isPassword = $type === 'password';
    $isTextarea = $type === 'textarea';
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
    $inputClasses = \Illuminate\Support\Arr::toCssClasses([
        'block w-full rounded-[10px] border bg-white px-3.5 text-[15px] text-saldo-ink shadow-none placeholder:text-saldo-placeholder',
        'focus:border-saldo-ink focus:ring-[3px] focus:ring-saldo-ink/10 focus:outline-none',
        'h-11 py-0' => ! $isTextarea,
        'min-h-[84px] resize-y py-[11px] leading-[1.45]' => $isTextarea,
        'border-saldo-line-strong' => ! $error,
        'border-saldo-danger bg-saldo-danger-bg' => $error,
        'pr-12' => $isPassword,
        'pl-7' => $prefix,
    ]);
@endphp

<div class="flex flex-col gap-1.5">
    <div class="flex items-baseline justify-between gap-3">
        <label for="{{ $id }}" class="text-[13.5px] font-medium">{{ $label }}</label>
        @isset($aside)
            {{ $aside }}
        @elseif ($optional)
            <span class="text-[12.5px] text-saldo-subtle">Optional</span>
        @endisset
    </div>

    @if ($isPassword)
        <div x-data="{ show: false }" class="relative">
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="password"
                x-bind:type="show ? 'text' : 'password'"
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->class([$inputClasses]) }}
            >
            <button
                type="button"
                x-on:click="show = ! show"
                x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                x-bind:aria-pressed="show"
                aria-label="Show password"
                class="absolute right-0.5 top-0.5 flex size-10 cursor-pointer items-center justify-center rounded-lg text-saldo-muted transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink"
            >
                <svg x-show="! show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <svg x-show="show" x-cloak style="display: none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.7 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.6 3.6M6.6 6.6C3.7 8.4 2 12 2 12s3.5 7 10 7c1.7 0 3.2-.4 4.5-1.1"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path><path d="m3 3 18 18"></path></svg>
            </button>
        </div>
    @elseif ($isTextarea)
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="3"
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([$inputClasses]) }}
        >{{ old($name, $value) }}</textarea>
    @elseif ($prefix)
        <div class="relative">
            <span aria-hidden="true" class="pointer-events-none absolute left-3.5 top-[11px] text-[15px] text-saldo-subtle">{{ $prefix }}</span>
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ old($name, $value) }}"
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->class([$inputClasses]) }}
            >
        </div>
    @else
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([$inputClasses]) }}
        >
    @endif

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-[13px] leading-[1.45] text-saldo-subtle">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-2 text-[13.5px] leading-[1.45] text-saldo-danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-px flex-none"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path></svg>
            <span>{{ $error }} {{ $errorAction ?? '' }}</span>
        </p>
    @endif
</div>
