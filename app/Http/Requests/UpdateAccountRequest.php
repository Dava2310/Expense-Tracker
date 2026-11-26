<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateAccountRequest extends StoreAccountRequest
{
    /**
     * Who can update an account is decided by the account policy, which the controller asks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bank' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:255'],
            'min_balance' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
        ];
    }
}
