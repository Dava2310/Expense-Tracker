@php
    $user = auth()->user();
    $initials = str($user->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $iconAttributes = 'width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
@endphp

<header class="border-b border-saldo-line bg-white">
    <div class="mx-auto flex min-h-[72px] max-w-[1184px] flex-wrap items-center justify-between gap-x-6 gap-y-3 px-8 py-3">

        <a href="{{ route('dashboard') }}" aria-label="Saldo, go to dashboard" class="flex items-center gap-2.5 text-saldo-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
            <x-saldo.logo />
            <span class="font-display text-[19px] font-semibold tracking-[-0.02em]">Saldo</span>
        </a>

        <nav aria-label="Main" class="order-last w-full overflow-x-auto lg:order-none lg:w-auto">
            <div class="flex w-max gap-0.5 rounded-xl bg-[#f1f1ee] p-1">
                <x-saldo.nav-item :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    <x-slot:icon>
                        <svg {!! $iconAttributes !!}><rect x="3" y="3" width="7.5" height="7.5" rx="2"></rect><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"></rect><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"></rect><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"></rect></svg>
                    </x-slot:icon>
                    Dashboard
                </x-saldo.nav-item>

                <x-saldo.nav-item :href="route('accounts.index')" :active="request()->routeIs('accounts.*')">
                    <x-slot:icon>
                        <svg {!! $iconAttributes !!}><rect x="3" y="5.5" width="18" height="14" rx="2.5"></rect><path d="M3 10h18"></path><path d="M16.5 15h1.5"></path></svg>
                    </x-slot:icon>
                    Accounts
                </x-saldo.nav-item>

                <x-saldo.nav-item>
                    <x-slot:icon>
                        <svg {!! $iconAttributes !!}><path d="M7 17 17 7"></path><path d="M8 7h9v9"></path></svg>
                    </x-slot:icon>
                    Expenses
                </x-saldo.nav-item>

                <x-saldo.nav-item>
                    <x-slot:icon>
                        <svg {!! $iconAttributes !!}><path d="M17 7 7 17"></path><path d="M16 17H7V8"></path></svg>
                    </x-slot:icon>
                    Incomes
                </x-saldo.nav-item>

                <x-saldo.nav-item>
                    <x-slot:icon>
                        <svg {!! $iconAttributes !!}><path d="M3 4.5v6.1c0 .5.2 1 .6 1.4l8.4 8.4a2 2 0 0 0 2.8 0l5.7-5.7a2 2 0 0 0 0-2.8L12 3.6c-.4-.4-.9-.6-1.4-.6H4.5C3.7 3 3 3.7 3 4.5Z"></path><circle cx="7.5" cy="7.5" r="1.25"></circle></svg>
                    </x-slot:icon>
                    Categories
                </x-saldo.nav-item>
            </div>
        </nav>

        <div class="flex items-center gap-2">
            <div x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" class="relative">
                <button
                    type="button"
                    x-on:click="open = ! open"
                    aria-haspopup="menu"
                    x-bind:aria-expanded="open"
                    class="flex h-11 cursor-pointer items-center gap-2.5 rounded-[10px] px-2 text-sm font-medium transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink"
                >
                    <span aria-hidden="true" class="flex size-8 items-center justify-center rounded-full bg-saldo-green-tint text-[12.5px] font-semibold text-saldo-green">{{ $initials }}</span>
                    <span class="hidden max-w-40 truncate sm:block">{{ $user->name }}</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="text-saldo-subtle"><path d="m6 9 6 6 6-6"></path></svg>
                </button>

                <div x-show="open" x-transition.opacity.duration.100ms style="display: none" role="menu" class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-saldo-line bg-white p-1.5 shadow-[0_16px_40px_-16px_rgba(22,24,27,0.25)]">
                    <div class="flex flex-col gap-0.5 px-3 py-2">
                        <span class="truncate text-sm font-semibold">{{ $user->name }}</span>
                        <span class="truncate text-[13px] text-saldo-subtle">{{ $user->email }}</span>
                    </div>
                    <div class="my-1 h-px bg-saldo-line-soft"></div>
                    <a href="{{ route('profile.edit') }}" role="menuitem" class="flex h-10 items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-saldo-ink no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-saldo-ink">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                        Profile
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-[10px] border border-saldo-line bg-white px-3.5 text-sm font-medium text-saldo-ink transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path></svg>
                    <span>Log out</span>
                </button>
            </form>
        </div>

    </div>
</header>
