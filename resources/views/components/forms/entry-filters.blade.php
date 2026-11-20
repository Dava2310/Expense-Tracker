@props(['action', 'periods', 'period', 'from', 'to', 'accounts', 'accountId', 'categories', 'categoryId', 'activeFilters'])

<form
    method="GET"
    action="{{ $action }}"
    x-data="{ period: '{{ $period }}' }"
    class="flex flex-col gap-4 rounded-[14px] border border-saldo-line bg-white p-4"
>
    <div class="flex flex-wrap items-end gap-x-4 gap-y-3">
        <x-forms.select label="Period" name="period" id="filter-period" class="min-w-[220px]" x-model="period" x-on:change="period !== 'custom' && $el.form.requestSubmit()">
            @foreach ($periods as $value => $label)
                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
            @endforeach
        </x-forms.select>

        <x-forms.select label="Account" name="account" id="filter-account" class="min-w-[220px]" x-on:change="$el.form.requestSubmit()">
            <option value="">All accounts</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected($accountId === $account->id)>{{ $account->name }}{{ $account->trashed() ? ' (inactive)' : '' }}</option>
            @endforeach
        </x-forms.select>

        <x-forms.select label="Category" name="category" id="filter-category" class="min-w-[220px]" x-on:change="$el.form.requestSubmit()">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-forms.select>

        @if ($activeFilters > 0)
            <a href="{{ $action }}" class="inline-flex h-11 items-center rounded-[10px] px-3 text-sm font-medium text-saldo-muted no-underline transition-colors hover:bg-saldo-ghost-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink">Clear filters</a>
        @endif
    </div>

    <div
        x-show="period === 'custom'"
        @if ($period !== 'custom') style="display: none" @endif
        class="flex flex-wrap items-end gap-x-4 gap-y-3"
    >
        <x-forms.field label="From" name="from" id="filter-from" type="date" :value="$from" class="min-w-[170px]" x-bind:disabled="period !== 'custom'" x-on:change="$el.form.requestSubmit()" />
        <x-forms.field label="To" name="to" id="filter-to" type="date" :value="$to" class="min-w-[170px]" x-bind:disabled="period !== 'custom'" x-on:change="$el.form.requestSubmit()" />
    </div>

    <noscript>
        <x-saldo.button as="button" class="self-start">Apply</x-saldo.button>
    </noscript>
</form>
