<x-layout.app>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('incomes.index')">Incomes</x-ui.back-link>

        <x-ui.entry-detail :entry="$income" kind="income" />
    </div>
</x-layout.app>
