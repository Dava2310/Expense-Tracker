@php
    $isGuest = ! auth()->check();
    $primaryHref = $isGuest ? route('register') : route('dashboard');
    $primaryLabel = $isGuest ? 'Create your account' : 'Go to your dashboard';

    $previewAccounts = [
        ['name' => 'Everyday', 'bank' => 'Harbor Bank', 'balance' => '$2,480.15', 'low' => false],
        ['name' => 'Savings', 'bank' => 'Harbor Bank', 'balance' => '$12,350.00', 'low' => false],
        ['name' => 'Travel fund', 'bank' => 'Summit Credit Union', 'balance' => '$640.20', 'low' => false],
        ['name' => 'Wallet', 'bank' => 'Cash', 'balance' => '$86.40', 'low' => true],
    ];

    $previewActivity = [
        ['concept' => 'Weekly groceries', 'meta' => 'Today · Groceries', 'amount' => '−$84.30', 'income' => false],
        ['concept' => 'Metro card top-up', 'meta' => 'Yesterday · Transport', 'amount' => '−$40.00', 'income' => false],
        ['concept' => 'Logo project', 'meta' => 'Nov 10 · Freelance', 'amount' => '+$450.00', 'income' => true],
        ['concept' => 'Electricity bill', 'meta' => 'Nov 10 · Utilities', 'amount' => '−$62.18', 'income' => false],
    ];

    $features = [
        ['title' => 'Every account, one total', 'body' => 'Add checking, savings or cash. Set a minimum for each one, and Saldo flags any account that dips below it.'],
        ['title' => 'Log it, and the balance follows', 'body' => 'Record an expense or an income with an amount, a concept and a date. The account updates instantly, so your numbers are always current.'],
        ['title' => 'Categories that fit your life', 'body' => 'Name categories your way — Groceries, Rent, Freelance — and filter by account to see where money comes from and where it goes.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Saldo') }} — Your money, in plain sight</title>
    <meta name="description" content="Saldo keeps your bank accounts, expenses and incomes together. Log a payment and the balance updates on its own.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|space-grotesk:500,600&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif
</head>
<body class="min-h-screen bg-saldo-canvas font-inter text-saldo-ink antialiased">

    <header class="mx-auto flex max-w-[1184px] flex-wrap items-center justify-between gap-4 px-8 py-5">
        <a href="{{ route('welcome') }}" aria-label="Saldo home" class="flex items-center gap-2.5 text-saldo-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
            <x-saldo.logo />
            <span class="font-display text-xl font-semibold tracking-[-0.02em]">Saldo</span>
        </a>

        <nav aria-label="Account" class="flex items-center gap-2">
            @auth
                <x-saldo.button :href="route('dashboard')">Dashboard</x-saldo.button>
            @else
                <x-saldo.button variant="ghost" :href="route('login')">Log in</x-saldo.button>
                <x-saldo.button :href="route('register')" class="px-4.5">Get started</x-saldo.button>
            @endauth
        </nav>
    </header>

    <main>
        <section class="mx-auto flex max-w-[880px] flex-col items-center gap-6 px-8 pb-16 pt-22 text-center">
            <p class="inline-flex items-center gap-2 rounded-full border border-saldo-line bg-white px-3.5 py-1.5 text-[13.5px] font-medium text-saldo-muted">
                <span aria-hidden="true" class="size-1.5 rounded-full bg-saldo-green"></span>
                <span>Accounts, expenses and incomes in one place</span>
            </p>

            <h1 class="font-display text-[clamp(2.75rem,6vw,4.5rem)] font-semibold leading-[1.02] tracking-[-0.035em] text-balance">
                Your money, in plain sight.
            </h1>

            <p class="max-w-[600px] text-lg leading-[1.6] text-saldo-muted text-pretty">
                Saldo keeps your bank accounts, expenses and incomes together. Log a payment and the balance updates on its own — no spreadsheets, no formulas.
            </p>

            <div class="flex flex-col items-center gap-3.5 pt-2">
                <x-saldo.button size="lg" :href="$primaryHref">
                    {{ $primaryLabel }}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </x-saldo.button>

                @guest
                    <p class="text-sm text-saldo-subtle">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-medium text-saldo-ink underline">Log in</a>
                    </p>
                @endguest
            </div>
        </section>

        <section aria-label="Product preview" class="mx-auto max-w-[1184px] px-8">
            <div class="overflow-hidden rounded-[22px] border border-saldo-line bg-white shadow-[0_40px_80px_-32px_rgba(22,24,27,0.22),0_2px_6px_rgba(22,24,27,0.04)]">
                <div aria-hidden="true" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-saldo-line-soft px-6 py-3.5">
                    <span class="flex items-center gap-2 font-display text-base font-semibold tracking-[-0.02em]">
                        <x-saldo.logo :size="22" />
                        <span>Saldo</span>
                    </span>

                    <span class="flex flex-wrap gap-0.5 rounded-[10px] bg-[#f1f1ee] p-1 text-[13px] font-medium text-saldo-muted">
                        <span class="rounded-[7px] bg-white px-3 py-1.5 text-saldo-ink shadow-[0_1px_2px_rgba(22,24,27,0.08)]">Accounts</span>
                        <span class="px-3 py-1.5">Expenses</span>
                        <span class="px-3 py-1.5">Incomes</span>
                        <span class="px-3 py-1.5">Categories</span>
                    </span>

                    <span class="flex size-[30px] items-center justify-center rounded-full bg-saldo-green-tint text-xs font-semibold text-saldo-green">AR</span>
                </div>

                <div class="grid grid-cols-[repeat(auto-fit,minmax(min(440px,100%),1fr))] gap-px bg-saldo-line-soft">
                    <div class="flex flex-col gap-[18px] bg-white px-8 py-7">
                        <div class="flex flex-col gap-1">
                            <span class="text-[13.5px] text-saldo-subtle">Total balance</span>
                            <span class="font-display text-[40px] font-medium tracking-[-0.025em] tabular-nums">$15,556.75</span>
                        </div>

                        <ul>
                            @foreach ($previewAccounts as $account)
                                <li class="flex items-center justify-between gap-3 border-t border-saldo-line-soft py-3.5">
                                    <span class="flex min-w-0 flex-col gap-0.5">
                                        <span class="text-[15px] font-medium">{{ $account['name'] }}</span>
                                        <span class="text-[13px] text-saldo-subtle">{{ $account['bank'] }}</span>
                                    </span>
                                    <span class="flex items-center gap-2.5">
                                        @if ($account['low'])
                                            <span class="inline-flex items-center rounded-full bg-saldo-warn-bg px-2.5 py-1 text-[12.5px] font-medium text-saldo-warn-fg">Below minimum</span>
                                        @endif
                                        <span class="text-[15px] font-medium tabular-nums">{{ $account['balance'] }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex flex-col gap-[18px] bg-white px-8 py-7">
                        <span class="text-[13.5px] text-saldo-subtle">Latest activity</span>

                        <ul>
                            @foreach ($previewActivity as $item)
                                <li class="flex items-center gap-3.5 border-t border-saldo-line-soft py-3">
                                    <span aria-hidden="true" @class([
                                        'flex size-9 flex-none items-center justify-center rounded-[10px]',
                                        'bg-saldo-green-tint text-saldo-green' => $item['income'],
                                        'bg-[#f0f0ec] text-saldo-muted' => ! $item['income'],
                                    ])>
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                            @if ($item['income'])
                                                <path d="M17 7 7 17"></path><path d="M16 17H7V8"></path>
                                            @else
                                                <path d="M7 17 17 7"></path><path d="M8 7h9v9"></path>
                                            @endif
                                        </svg>
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span class="text-[15px] font-medium">{{ $item['concept'] }}</span>
                                        <span class="text-[13px] text-saldo-subtle">{{ $item['meta'] }}</span>
                                    </span>
                                    <span @class([
                                        'text-[15px] font-medium tabular-nums',
                                        'text-saldo-green' => $item['income'],
                                    ])>{{ $item['amount'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-[1184px] grid-cols-[repeat(auto-fit,minmax(min(300px,100%),1fr))] gap-x-10 gap-y-12 px-8 pb-24 pt-28">
            @foreach ($features as $feature)
                <div class="flex flex-col gap-3 border-t border-saldo-line-strong pt-5">
                    <span class="font-display text-sm font-medium text-saldo-green">{{ sprintf('%02d', $loop->iteration) }}</span>
                    <h3 class="font-display text-[22px] font-semibold tracking-[-0.02em]">{{ $feature['title'] }}</h3>
                    <p class="text-base leading-[1.6] text-saldo-muted">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="mx-auto max-w-[1184px] px-8 pb-8">
            <div class="flex flex-col items-center gap-5 rounded-3xl bg-saldo-ink px-8 py-18 text-center">
                <h2 class="font-display text-[clamp(2rem,4vw,3rem)] font-semibold leading-[1.08] tracking-[-0.03em] text-white text-balance">
                    Start with one account.
                </h2>
                <p class="max-w-[480px] text-[17px] leading-[1.6] text-saldo-dark-text">
                    Add it, log today’s spending, and let the balance keep itself up to date.
                </p>
                <x-saldo.button variant="light" size="lg" :href="$primaryHref">{{ $primaryLabel }}</x-saldo.button>
            </div>
        </section>
    </main>

    <footer class="mx-auto flex max-w-[1184px] flex-wrap items-center justify-between gap-3 px-8 pb-12 pt-8 text-[13.5px] text-saldo-subtle">
        <span class="flex items-center gap-2 font-display text-[15px] font-semibold text-saldo-ink">
            <x-saldo.logo :size="20" />
            <span>Saldo</span>
        </span>
        <span>© {{ now()->year }} Saldo</span>
    </footer>

</body>
</html>
