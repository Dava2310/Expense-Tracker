<x-layout.app title="New currency">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('currencies.index')">Currencies</x-ui.back-link>

        <x-ui.page-header title="New currency">
            <x-slot:subtitle>Add a currency so you can open accounts in it.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.currency :action="route('currencies.store')" cta="Create currency" />
    </div>
</x-layout.app>
