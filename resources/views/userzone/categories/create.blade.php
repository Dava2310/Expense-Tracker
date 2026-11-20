<x-layout.app title="New category">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('categories.index')">Categories</x-ui.back-link>

        <x-ui.page-header title="New category">
            <x-slot:subtitle>You can use it for both expenses and incomes.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.category :action="route('categories.store')" cta="Create category" />
    </div>
</x-layout.app>
