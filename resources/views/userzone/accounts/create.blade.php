<x-layout.app>
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('accounts.index')">Accounts</x-ui.back-link>

        <x-ui.page-header title="New account">
            <x-slot:subtitle>A bank account, a savings pot or the cash in your wallet.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.account :action="route('accounts.store')" :currencies="$currencies" cta="Create account" />
    </div>
</x-layout.app>
