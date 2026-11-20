<x-layout.app title="Currencies">
    {{-- "currency" holds the row whose delete button was clicked, for the confirmation dialog below --}}
    <div x-data="{ currency: {} }" class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-10">
        <x-ui.page-header title="Currencies">
            <x-slot:subtitle>
                {{ $currencies->count() }} {{ str('currency')->plural($currencies->count()) }}. Every account uses one of them.
            </x-slot:subtitle>

            <x-slot:actions>
                <x-ui.button :href="route('currencies.create')" class="gap-2 pl-3.5 pr-4.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    <span>New currency</span>
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status') === 'currency-created')
            <x-ui.status status="Currency created." />
        @endif
        @if (session('status') === 'currency-updated')
            <x-ui.status status="Currency updated." />
        @endif
        @if (session('status') === 'currency-deleted')
            <x-ui.status status="Currency deleted." />
        @endif
        @error('currency')
            <p role="alert" class="rounded-[10px] border border-saldo-danger bg-saldo-danger-bg px-3.5 py-3 text-sm font-medium leading-normal text-saldo-danger">{{ $message }}</p>
        @enderror

        @if ($currencies->isEmpty())
            <section class="flex flex-col items-center gap-2.5 rounded-2xl border border-dashed border-saldo-line-strong px-6 py-14 text-center">
                <p class="text-base font-semibold">No currencies yet</p>
                <p class="text-[14.5px] text-saldo-muted">Every account is held in one of your currencies.</p>
            </section>
        @else
            <section aria-label="All currencies" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
                <div class="overflow-x-auto">
                    <div class="min-w-[640px]">
                        <div aria-hidden="true" class="grid grid-cols-[minmax(200px,2fr)_minmax(100px,1fr)_140px_136px] gap-4 border-b border-saldo-line-soft bg-[#fafaf8] py-3 pl-5 pr-3 text-[12.5px] font-medium tracking-[0.02em] text-saldo-subtle">
                            <span>Name</span>
                            <span>Symbol</span>
                            <span>Used by</span>
                            <span></span>
                        </div>

                        <ul>
                            @foreach ($currencies as $currency)
                                {{-- A currency in use by accounts can't be deleted, so its delete button is disabled --}}
                                @php
                                    $accounts = $currency->accounts_count ?? 0;
                                    $accountsText = $accounts.' '.str('account')->plural($accounts);

                                    $selection = ['name' => $currency->name, 'symbol' => $currency->symbol, 'action' => route('currencies.destroy', $currency)];
                                @endphp

                                <li class="grid min-h-16 grid-cols-[minmax(200px,2fr)_minmax(100px,1fr)_140px_136px] items-center gap-4 border-t border-saldo-line-soft py-2 pl-5 pr-3 transition-colors first:border-t-0 hover:bg-[#f7f7f4]">
                                    <span class="truncate text-[15px] font-medium">{{ $currency->name }}</span>

                                    <span>
                                        <span class="inline-flex h-[30px] min-w-9 items-center justify-center rounded-lg bg-[#f1f1ee] px-2.5 font-display text-[15px] font-semibold">{{ $currency->symbol }}</span>
                                    </span>

                                    <span class="text-[13.5px] tabular-nums text-saldo-subtle">
                                        {{ $accounts > 0 ? $accountsText : 'No accounts' }}
                                    </span>

                                    <span class="flex justify-end gap-1">
                                        <a href="{{ route('currencies.show', $currency) }}" aria-label="View {{ $currency->name }}" title="View" class="flex size-10 items-center justify-center rounded-[10px] text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        </a>

                                        <a href="{{ route('currencies.edit', $currency) }}" aria-label="Edit {{ $currency->name }}" title="Edit" class="flex size-10 items-center justify-center rounded-[10px] text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                                        </a>

                                        <button
                                            type="button"
                                            aria-label="Delete {{ $currency->name }}"
                                            title="{{ $accounts > 0 ? 'In use by '.$accountsText.', so it can’t be deleted' : 'Delete' }}"
                                            @if ($accounts > 0)
                                                disabled
                                            @else
                                                aria-haspopup="dialog"
                                                x-on:click="currency = @js($selection); $dispatch('open-modal', 'confirm-currency-deletion')"
                                            @endif
                                            @class([
                                                'flex size-10 items-center justify-center rounded-[10px] transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink',
                                                'cursor-not-allowed text-saldo-subtle opacity-50' => $accounts > 0,
                                                'cursor-pointer text-saldo-muted hover:bg-saldo-ghost-hover' => $accounts === 0,
                                            ])
                                        >
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
                                        </button>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        <x-ui.modal name="confirm-currency-deletion" labelledby="delete-title" describedby="delete-text">
            <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-[#fdecea] text-saldo-danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
            </span>

            <div class="flex flex-col gap-2">
                <h2 id="delete-title" class="font-display text-[22px] font-semibold tracking-[-0.02em]">Delete <span x-text="currency.name"></span> (<span x-text="currency.symbol"></span>)?</h2>
                <p id="delete-text" class="text-[15px] leading-[1.55] text-saldo-muted">No accounts use it, so nothing else changes. This can’t be undone.</p>
            </div>

            <form method="POST" x-bind:action="currency.action" class="flex flex-wrap justify-end gap-2 pt-1">
                @csrf
                @method('DELETE')

                <x-ui.button as="button" type="button" variant="outline" x-on:click="$dispatch('close')">Cancel</x-ui.button>
                <x-ui.button as="button" variant="danger" class="px-4.5">Delete currency</x-ui.button>
            </form>
        </x-ui.modal>
    </div>
</x-layout.app>
