<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('incomes.index')">Incomes</x-saldo.back-link>

        <x-saldo.entry-detail :entry="$income" kind="income" />
    </div>
</x-app-layout>
