<x-layout.app>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('expenses.index')">Expenses</x-ui.back-link>

        <x-ui.entry-detail :entry="$expense" kind="expense" />
    </div>
</x-layout.app>
