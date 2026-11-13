<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Accounts
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                {{-- Success message --}}
                @if (session('status') === 'account-created')
                    <p class="mb-4 text-sm text-green-600">Account created.</p>
                @endif
                @if (session('status') === 'account-updated')
                    <p class="mb-4 text-sm text-green-600">Account updated.</p>
                @endif

                <div class="mb-4">
                    <a href="{{ route('accounts.create') }}" class="underline text-sm text-gray-600 hover:text-gray-900">Create Account</a>
                </div>

                {{-- Accounts table --}}
                <table class="w-full text-left text-sm text-gray-900">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-2 pe-4">Name</th>
                            <th class="py-2 pe-4">Bank</th>
                            <th class="py-2 pe-4">Details</th>
                            <th class="py-2 pe-4">Balance</th>
                            <th class="py-2 pe-4">Minimum balance</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pe-4">{{ $account->name }}</td>
                                <td class="py-2 pe-4">{{ $account->bank }}</td>
                                <td class="py-2 pe-4">{{ $account->details }}</td>
                                <td class="py-2 pe-4">€ {{ number_format($account->balance, 2) }}</td>
                                <td class="py-2 pe-4">€ {{ number_format($account->min_balance, 2) }}</td>
                                <td class="py-2">
                                    <a href="{{ route('accounts.edit', $account) }}" class="underline text-gray-600 hover:text-gray-900">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-600">You have no accounts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
