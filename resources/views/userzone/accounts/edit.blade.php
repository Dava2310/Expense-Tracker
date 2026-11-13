<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Account
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                <div>
                    {{-- Form --}}
                    <form method="POST" action="{{ route('accounts.update', $account) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        {{-- Name --}}
                        <div>
                            <x-breeze.input-label for="name" value="Name"/>
                            <x-breeze.text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $account->name)" required autofocus/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('name')"/>
                        </div>
                        {{-- Bank --}}
                        <div>
                            <x-breeze.input-label for="bank" value="Bank"/>
                            <x-breeze.text-input id="bank" name="bank" type="text" class="mt-1 block w-full" :value="old('bank', $account->bank)" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('bank')"/>
                        </div>
                        {{-- Details --}}
                        <div>
                            <x-breeze.input-label for="details" value="Details"/>
                            <x-breeze.text-input id="details" name="details" type="text" class="mt-1 block w-full" :value="old('details', $account->details)" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('details')"/>
                        </div>
                        {{-- Minimum balance --}}
                        <div>
                            <x-breeze.input-label for="min_balance" value="Minimum balance"/>
                            <x-breeze.text-input id="min_balance" name="min_balance" type="number" step="0.01" min="0" max="999999.99" class="mt-1 block w-full" :value="old('min_balance', $account->min_balance)" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('min_balance')"/>
                        </div>
                        {{-- Buttons --}}
                        <div class="flex items-center gap-4">
                            <x-breeze.primary-button>Save</x-breeze.primary-button>
                            <a href="{{ route('accounts.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
