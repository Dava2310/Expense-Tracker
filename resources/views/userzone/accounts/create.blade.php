<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Account
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                <div class="max-w-xl">
                    {{-- Form --}}
                    <form method="POST" action="{{ route('accounts.store') }}" class="space-y-6">
                        @csrf
                        {{-- Name --}}
                        <div>
                            <x-breeze.input-label for="name" value="Name"/>
                            <x-breeze.text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('name')"/>
                        </div>
                        {{-- Bank --}}
                        <div>
                            <x-breeze.input-label for="bank" value="Bank"/>
                            <x-breeze.text-input id="bank" name="bank" type="text" class="mt-1 block w-full" :value="old('bank')" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('bank')"/>
                        </div>
                        {{-- Details --}}
                        <div>
                            <x-breeze.input-label for="details" value="Details"/>
                            <x-breeze.text-input id="details" name="details" type="text" class="mt-1 block w-full" :value="old('details')" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('details')"/>
                        </div>
                        {{-- Balance --}}
                        <div>
                            <x-breeze.input-label for="balance" value="Balance"/>
                            <x-breeze.text-input id="balance" name="balance" type="number" step="0.01" min="0" max="999999.99" class="mt-1 block w-full" :value="old('balance')" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('balance')"/>
                        </div>
                        {{-- Minimum balance --}}
                        <div>
                            <x-breeze.input-label for="min_balance" value="Minimum balance"/>
                            <x-breeze.text-input id="min_balance" name="min_balance" type="number" step="0.01" min="0" max="999999.99" class="mt-1 block w-full" :value="old('min_balance')" required/>
                            <x-breeze.input-error class="mt-2" :messages="$errors->get('min_balance')"/>
                        </div>
                        {{-- Submit button --}}
                        <div class="flex items-center gap-4">
                            <x-breeze.primary-button>Create Account</x-breeze.primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
