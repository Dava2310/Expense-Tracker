<x-layout.app title="Edit category">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('categories.index')">Categories</x-ui.back-link>

        <x-ui.page-header title="Edit category">
            <x-slot:subtitle>Changes show up everywhere this category is used.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.category :action="route('categories.update', $category)" :category="$category" cta="Save changes" />
    </div>
</x-layout.app>
