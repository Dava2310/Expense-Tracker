{{-- Picks the account a dashboard chart looks at. Each chart has its own query parameter, so the other one is kept --}}
@props(['name', 'accounts', 'selected' => null, 'preserve' => []])

<form method="GET" action="{{ route('dashboard') }}" x-data class="flex-none">
    @foreach ($preserve as $preservedName => $preservedValue)
        @if ($preservedValue)
            <input type="hidden" name="{{ $preservedName }}" value="{{ $preservedValue }}">
        @endif
    @endforeach

    <label for="filter-{{ $name }}" class="sr-only">Account</label>
    <select
        id="filter-{{ $name }}"
        name="{{ $name }}"
        x-on:change="$el.form.requestSubmit()"
        class="h-9 max-w-[200px] cursor-pointer rounded-[9px] border border-saldo-line-strong bg-white py-0 pl-3 pr-8 text-[13.5px] text-saldo-ink shadow-none transition-colors hover:border-[#c9c8c1] focus:border-saldo-ink focus:ring-[3px] focus:ring-saldo-ink/10 focus:outline-none"
    >
        <option value="" @selected(! $selected)>Choose an account</option>
        @foreach ($accounts as $account)
            <option value="{{ $account->id }}" @selected($selected === $account->id)>{{ $account->name }}</option>
        @endforeach
    </select>
</form>
