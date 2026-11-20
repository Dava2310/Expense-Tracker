@props(['action', 'cta', 'category' => null])

<form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
    @csrf
    @if ($category)
        @method('PATCH')
    @endif

    <div class="flex flex-col gap-5 p-6">
        <x-forms.field label="Name" name="name" :value="$category?->name" placeholder="e.g. Pets" required autofocus />

        <x-forms.field label="Details" name="description" type="textarea" :value="$category?->description" optional placeholder="What goes in this category" />

        @if ($category)
            @php($entries = $category->incomes_count + $category->expenses_count)

            <p class="flex items-start gap-2.5 rounded-xl bg-saldo-canvas px-3.5 py-3 text-[13.5px] leading-normal text-saldo-muted">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-px flex-none"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8h.01"></path></svg>
                <span>
                    @if ($entries > 0)
                        Used by {{ $entries }} {{ str('entry')->plural($entries) }}. Those entries keep their place and pick up the new name.
                    @else
                        Not used by any entries yet.
                    @endif
                </span>
            </p>
        @endif
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ route('categories.index') }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-ui.button as="button" class="px-4.5">{{ $cta }}</x-ui.button>
    </div>
</form>
