<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * The plain-words names of the fields, used by the messages of both the create and the edit form.
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
     * Messages written for people, shared with the edit form (UpdateAccountRequest extends this class).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the account name.',
            'name.string' => 'The :attribute must be text.',
            'name.max' => 'The :attribute can have at most :max characters.',

            'bank.required' => 'Enter the name of the bank, or “Cash”.',
            'bank.string' => 'The :attribute must be text.',
            'bank.max' => 'The :attribute can have at most :max characters.',

            'details.string' => 'The :attribute must be text.',
            'details.max' => 'The :attribute can have at most :max characters.',

            'currency_id.required' => 'Choose the currency of the account.',
            'currency_id.exists' => 'Choose one of your own currencies.',

            'min_balance.numeric' => 'Enter the :attribute as a number, like 100.00.',
            'min_balance.min' => 'The :attribute can’t be negative. Enter 0 or more.',
            'min_balance.decimal' => 'Use at most 2 decimals in the :attribute, like 100.00.',
            'min_balance.max' => 'The :attribute can’t be more than :max.',
        ];
    }
}
