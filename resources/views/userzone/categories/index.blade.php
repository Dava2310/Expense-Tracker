<x-layout.app>
    {{-- "category" holds the row whose delete button was clicked, for the two dialogs below --}}
    <div x-data="{ category: {} }" class="mx-auto flex max-w-[1184px] flex-col gap-6 px-8 pb-18 pt-10">
        <x-ui.page-header title="Categories">
            <x-slot:subtitle>
                {{ $categories->count() }} {{ str('category')->plural($categories->count()) }}, shared by your expenses and incomes.
            </x-slot:subtitle>

            <x-slot:actions>
                <x-ui.button :href="route('categories.create')" class="gap-2 pl-3.5 pr-4.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    <span>New category</span>
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status') === 'category-created')
            <x-ui.status status="Category created." />
        @endif
        @if (session('status') === 'category-updated')
            <x-ui.status status="Category updated." />
        @endif
        @if (session('status') === 'category-deleted')
            <x-ui.status status="Category deleted." />
        @endif
        @error('category')
            <p role="alert" class="rounded-[10px] border border-saldo-danger bg-saldo-danger-bg px-3.5 py-3 text-sm font-medium leading-normal text-saldo-danger">{{ $message }}</p>
        @enderror

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
                                @php
                                    $entries = $category->incomes_count + $category->expenses_count;
                                    $entriesText = $entries.' '.str('entry')->plural($entries);

                                    // A category in use can't be deleted, so it opens the dialog that explains why
                                    $dialog = $entries > 0 ? 'category-in-use' : 'confirm-category-deletion';
                                    $selection = ['name' => $category->name, 'entries' => $entriesText, 'action' => route('categories.destroy', $category)];
                                @endphp

                                <li class="grid min-h-15 grid-cols-[minmax(150px,1fr)_minmax(0,2fr)_120px_92px] items-center gap-4 border-t border-saldo-line-soft py-2 pl-5 pr-3 first:border-t-0">
                                    <span class="text-[15px] font-medium">{{ $category->name }}</span>

                                    @if ($category->description)
                                        <span class="truncate text-sm text-saldo-muted">{{ $category->description }}</span>
                                    @else
                                        <span class="text-sm text-saldo-subtle">No details</span>
                                    @endif

                                    <span class="text-[13.5px] tabular-nums text-saldo-subtle">
                                        {{ $entries > 0 ? $entriesText : 'Not used yet' }}
                                    </span>

                                    <span class="flex justify-end gap-1">
                                        <a href="{{ route('categories.edit', $category) }}" aria-label="Edit {{ $category->name }}" class="flex size-10 items-center justify-center rounded-[10px] text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                                        </a>

                                        <button type="button" aria-label="Delete {{ $category->name }}" aria-haspopup="dialog" x-on:click="category = @js($selection); $dispatch('open-modal', @js($dialog))" @class([
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

        <x-ui.modal name="category-in-use" labelledby="in-use-title" describedby="in-use-text">
            <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-saldo-warn-bg text-saldo-warn-fg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
            </span>

            <div class="flex flex-col gap-2">
                <h2 id="in-use-title" class="font-display text-[22px] font-semibold tracking-[-0.02em]"><span x-text="category.name"></span> can’t be deleted</h2>
                <p id="in-use-text" class="text-[15px] leading-[1.55] text-saldo-muted">It’s used by <span x-text="category.entries"></span> in your expenses and incomes. A category that’s in use can’t be deleted, so your history stays accurate.</p>
            </div>

            <div class="flex justify-end pt-1">
                <x-ui.button as="button" type="button" class="px-5" x-on:click="$dispatch('close')">Got it</x-ui.button>
            </div>
        </x-ui.modal>

        <x-ui.modal name="confirm-category-deletion" labelledby="delete-title" describedby="delete-text">
            <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-[#fdecea] text-saldo-danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path></svg>
            </span>

            <div class="flex flex-col gap-2">
                <h2 id="delete-title" class="font-display text-[22px] font-semibold tracking-[-0.02em]">Delete <span x-text="category.name"></span>?</h2>
                <p id="delete-text" class="text-[15px] leading-[1.55] text-saldo-muted">It isn’t used by any expenses or incomes, so nothing else changes. This can’t be undone.</p>
            </div>

            <form method="POST" x-bind:action="category.action" class="flex flex-wrap justify-end gap-2 pt-1">
                @csrf
                @method('DELETE')

                <x-ui.button as="button" type="button" variant="outline" x-on:click="$dispatch('close')">Cancel</x-ui.button>
                <x-ui.button as="button" variant="danger" class="px-4.5">Delete category</x-ui.button>
            </form>
        </x-ui.modal>
    </div>
</x-layout.app>
