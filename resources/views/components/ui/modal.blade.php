@props([
    'name',
    'show' => false,
    'labelledby' => null,
    'describedby' => null,
    'maxWidth' => 'max-w-[440px]',
])

<div
    x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)].filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            setTimeout(() => firstFocusable()?.focus(), 100);
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="show && ($event.shiftKey ? prevFocusable().focus() : nextFocusable().focus())"
    x-show="show"
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:pt-[15vh]"
    style="display: {{ $show ? 'flex' : 'none' }};"
>
    <div
        x-show="show"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-saldo-ink/[0.42] backdrop-blur-[2px]"
        aria-hidden="true"
    ></div>

    <div
        x-show="show"
        role="dialog"
        aria-modal="true"
        @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
        @if ($describedby) aria-describedby="{{ $describedby }}" @endif
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="relative flex w-full flex-col gap-5 rounded-[18px] bg-white p-7 shadow-[0_24px_64px_-12px_rgba(22,24,27,0.30),0_2px_8px_rgba(22,24,27,0.06)] {{ $maxWidth }}"
    >
        {{ $slot }}
    </div>
</div>
