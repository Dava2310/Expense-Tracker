<header class="border-b border-saldo-line bg-white">
    <div class="mx-auto flex min-h-[72px] max-w-[1184px] flex-wrap items-center justify-between gap-x-6 gap-y-3 px-8 py-3">

        <a href="{{ route('dashboard') }}" aria-label="Saldo, go to dashboard" class="flex items-center gap-2.5 text-saldo-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
            <x-ui.logo />
            <span class="font-display text-[19px] font-semibold tracking-[-0.02em]">Saldo</span>
        </a>

        <nav aria-label="Main" class="order-last w-full overflow-x-auto">
            <div class="flex w-full min-w-max gap-0.5 rounded-xl bg-[#f1f1ee] p-1 *:flex-1 *:justify-center">
                @foreach ($items as $item)
                    <x-layout.nav-item :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                        <x-slot:icon>
                            <x-ui.icon :name="$item['icon']" />
                        </x-slot:icon>
                        {{ $item['label'] }}
                    </x-layout.nav-item>
                @endforeach
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
                    <x-ui.avatar :user="$user" />
                    <span class="hidden max-w-40 truncate sm:block">{{ $user->name }}</span>
                    <x-ui.icon name="chevron-down" width="16" height="16" class="text-saldo-subtle" />
                </button>

                <div x-show="open" x-transition.opacity.duration.100ms style="display: none" role="menu" class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-saldo-line bg-white p-1.5 shadow-[0_16px_40px_-16px_rgba(22,24,27,0.25)]">
                    <div class="flex flex-col gap-0.5 px-3 py-2">
                        <span class="truncate text-sm font-semibold">{{ $user->name }}</span>
                        <span class="truncate text-[13px] text-saldo-subtle">{{ $user->email }}</span>
                    </div>
                    <div class="my-1 h-px bg-saldo-line-soft"></div>
                    <a href="{{ route('profile.edit') }}" role="menuitem" class="flex h-10 items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-saldo-ink no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-saldo-ink">
                        <x-ui.icon name="user" />
                        Profile
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-[10px] border border-saldo-line bg-white px-3.5 text-sm font-medium text-saldo-ink transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                    <x-ui.icon name="logout" />
                    <span>Log out</span>
                </button>
            </form>
        </div>

    </div>
</header>
