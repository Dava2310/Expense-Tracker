<x-layout.app title="New transfer">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('accounts.index')">Accounts</x-ui.back-link>

        <x-ui.page-header title="New transfer">
            <x-slot:subtitle>Move money between two of your accounts. It is not an income or an expense.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.transfer :accounts="$accounts" />
    </div>
</x-layout.app>
