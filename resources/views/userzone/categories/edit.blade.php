<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-saldo.back-link :href="route('categories.index')">Categories</x-saldo.back-link>

        <x-saldo.page-header title="Edit category">
            <x-slot:subtitle>Changes show up everywhere this category is used.</x-slot:subtitle>
        </x-saldo.page-header>

        <x-saldo.category-form :action="route('categories.update', $category)" :category="$category" cta="Save changes" />
    </div>
</x-app-layout>
