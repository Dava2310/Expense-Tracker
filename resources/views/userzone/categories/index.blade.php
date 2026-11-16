<x-app-layout>
    <div class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-10">
        <x-saldo.page-header title="Categories">
            <x-slot:subtitle>
                {{ $categories->count() }} {{ str('category')->plural($categories->count()) }}, shared by your expenses and incomes.
            </x-slot:subtitle>

            <x-slot:actions>
                <x-saldo.button :href="route('categories.create')" class="gap-2 pl-3.5 pr-4.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    <span>New category</span>
                </x-saldo.button>
            </x-slot:actions>
        </x-saldo.page-header>

        @if (session('status') === 'category-created')
            <x-saldo.status status="Category created." />
        @endif
        @if (session('status') === 'category-updated')
            <x-saldo.status status="Category updated." />
        @endif

        @if ($categories->isEmpty())
            <section class="flex flex-col items-center gap-2.5 rounded-2xl border border-dashed border-saldo-line-strong px-6 py-14 text-center">
                <p class="text-base font-semibold">No categories yet</p>
                <p class="text-[14.5px] text-saldo-muted">Categories group your expenses and incomes.</p>
            </section>
        @else
            <section aria-label="All categories" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
                <div class="overflow-x-auto">
                    <div class="min-w-[680px]">
                        <div aria-hidden="true" class="grid grid-cols-[minmax(150px,1fr)_minmax(0,2fr)_120px_92px] gap-4 border-b border-saldo-line-soft bg-[#fafaf8] py-3 pl-5 pr-3 text-[12.5px] font-medium tracking-[0.02em] text-saldo-subtle">
                            <span>Name</span>
                            <span>Details</span>
                            <span>Used in</span>
                            <span></span>
                        </div>

                        <ul>
                            @foreach ($categories as $category)
                                {{-- Entries are the user's incomes and expenses that use the category --}}
                                @php($entries = $category->incomes_count + $category->expenses_count)

                                <li class="grid min-h-15 grid-cols-[minmax(150px,1fr)_minmax(0,2fr)_120px_92px] items-center gap-4 border-t border-saldo-line-soft py-2 pl-5 pr-3 first:border-t-0">
                                    <span class="text-[15px] font-medium">{{ $category->name }}</span>

                                    @if ($category->description)
                                        <span class="truncate text-sm text-saldo-muted">{{ $category->description }}</span>
                                    @else
                                        <span class="text-sm text-saldo-subtle">No details</span>
                                    @endif

                                    <span class="text-[13.5px] tabular-nums text-saldo-subtle">
                                        {{ $entries > 0 ? $entries.' '.str('entry')->plural($entries) : 'Not used yet' }}
                                    </span>

                                    {{-- The delete button is a placeholder until the delete feature exists --}}
                                    <span class="flex justify-end gap-1">
                                        <a href="{{ route('categories.edit', $category) }}" aria-label="Edit {{ $category->name }}" class="flex size-10 items-center justify-center rounded-[10px] text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                                        </a>

                                        <button type="button" aria-label="Delete {{ $category->name }}" aria-haspopup="dialog" @class([
                                            'flex size-10 cursor-pointer items-center justify-center rounded-[10px] transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink',
                                            'text-saldo-subtle' => $entries > 0,
                                            'text-saldo-muted' => $entries === 0,
                                        ])>
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
    </div>
</x-app-layout>
