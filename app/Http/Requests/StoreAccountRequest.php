<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the form that creates an account.
 */
class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'currency_id' => ['required', Rule::exists('currencies', 'id')->where('user_id', $this->user()->id)],
            'min_balance' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
        ];
    }

    /**
     * Plain-words names of the fields, used in the messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'account name',
            'bank' => 'bank',
            'details' => 'details',
            'currency_id' => 'currency',
            'min_balance' => 'minimum balance',
        ];
    }

    /**
     * Messages shown next to a field when one of its rules fails.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank.required' => 'Enter the name of the bank, or “Cash”.',
            'currency_id.required' => 'Choose the currency of the account.',
            'currency_id.exists' => 'Choose one of your own currencies.',
            'min_balance.decimal' => 'Use at most 2 decimals in the :attribute, like 100.00.',
        ];
    }
}
