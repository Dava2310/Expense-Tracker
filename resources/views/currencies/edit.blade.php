<x-app-layout>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('currencies.index')">Currencies</x-ui.back-link>

        <x-ui.page-header title="Edit currency">
            <x-slot:subtitle>Changes show up on every account that uses this currency.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.currency :action="route('currencies.update', $currency)" :currency="$currency" :accounts-count="$accountsCount" cta="Save changes" />
    </div>
</x-app-layout>
