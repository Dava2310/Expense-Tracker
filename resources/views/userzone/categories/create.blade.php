<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('categories.index')">Categories</x-saldo.back-link>

        <x-saldo.page-header title="New category">
            <x-slot:subtitle>You can use it for both expenses and incomes.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-forms.category :action="route('categories.store')" cta="Create category" />
    </div>
</x-app-layout>
