@props(['title', 'subtitle' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — {{ config('app.name', 'Saldo') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|space-grotesk:500,600&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js"></script>
    @endif
</head>
<body class="bg-saldo-canvas font-inter text-saldo-ink antialiased">
    <div class="flex min-h-screen flex-row-reverse flex-wrap">

        <main class="flex flex-[1_1_520px] items-center justify-center px-8 py-12">
            <div class="flex w-full max-w-[400px] flex-col gap-7">
                <div class="flex flex-col gap-2">
                    <h1 class="font-display text-[32px] font-semibold leading-[1.1] tracking-[-0.025em]">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="text-[15px] leading-normal text-saldo-muted">{{ $subtitle }}</p>
                    @endif
                </div>

                {{ $slot }}

                @isset($footer)
                    <p class="text-center text-sm text-saldo-muted">{{ $footer }}</p>
                @endisset
            </div>
        </main>

        <aside class="m-3 flex min-h-[360px] flex-[1_1_520px] flex-col justify-between gap-10 overflow-hidden rounded-3xl bg-saldo-ink px-12 py-10 text-white">
            <a href="{{ route('welcome') }}" aria-label="Saldo home" class="flex items-center gap-2.5 self-start text-white no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                <x-saldo.logo variant="light" />
                <span class="font-display text-xl font-semibold tracking-[-0.02em]">Saldo</span>
            </a>

            <div class="flex max-w-[460px] flex-col gap-8">
                <p class="font-display text-[40px] font-semibold leading-[1.1] tracking-[-0.03em] text-balance">
                    Every account. Every expense. One clear balance.
                </p>

                <div aria-hidden="true" class="flex flex-col gap-3.5 rounded-2xl border border-[#2e3237] bg-[#202327] p-5">
                    <div class="flex items-end justify-between gap-3">
                        <span class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium">Everyday</span>
                            <span class="text-[12.5px] text-[#9ea3aa]">Harbor Bank</span>
                        </span>
                        <span class="font-display text-2xl font-medium tabular-nums">$2,480.15</span>
                    </div>
                    <div class="h-px bg-[#2e3237]"></div>
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="flex flex-col gap-0.5">
                            <span>Weekly groceries</span>
                            <span class="text-[12.5px] text-[#9ea3aa]">Today · Groceries</span>
                        </span>
                        <span class="font-medium tabular-nums text-[#e6e7e9]">−$84.30</span>
                    </div>
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="flex flex-col gap-0.5">
                            <span>Logo project</span>
                            <span class="text-[12.5px] text-[#9ea3aa]">Nov 10 · Freelance</span>
                        </span>
                        <span class="font-medium tabular-nums text-[#5fd3a2]">+$450.00</span>
                    </div>
                </div>
            </div>

            <p class="text-[13px] text-[#9ea3aa]">© {{ now()->year }} Saldo</p>
        </aside>

    </div>
</body>
</html>
