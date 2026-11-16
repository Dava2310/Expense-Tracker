@props(['action', 'cta'])

<form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-saldo-line bg-white">
    @csrf

    <div class="flex flex-col gap-5 p-6">
        <x-saldo.field label="Name" name="name" placeholder="e.g. Pets" required autofocus />

        <x-saldo.field label="Details" name="description" type="textarea" optional placeholder="What goes in this category" />
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-saldo-line-soft bg-[#fafaf8] px-6 py-4">
        <a href="{{ route('categories.index') }}" class="inline-flex h-11 items-center justify-center rounded-[11px] border border-saldo-line-strong bg-white px-4 text-[14.5px] font-medium text-saldo-ink no-underline transition-colors hover:border-[#c9c8c1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Cancel</a>
        <x-saldo.button as="button" class="px-4.5">{{ $cta }}</x-saldo.button>
    </div>
</form>
