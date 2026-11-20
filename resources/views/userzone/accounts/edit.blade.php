<x-layout.app title="Edit account">
    <div class="mx-auto flex max-w-[680px] flex-col gap-5 px-8 pb-18 pt-7">
        <x-ui.back-link :href="route('accounts.show', $account)">{{ $account->name }}</x-ui.back-link>

        <x-ui.page-header title="Edit account">
            <x-slot:subtitle>Changes apply right away. Past expenses and incomes stay as they are.</x-slot:subtitle>
        </x-ui.page-header>

        <x-forms.account :action="route('accounts.update', $account)" :account="$account" cta="Save changes" />
    </div>
</x-layout.app>
