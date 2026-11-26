<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the form that creates a currency.
 */
class StoreCurrencyRequest extends FormRequest
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
            'symbol' => ['required', 'string', 'max:10'],
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
            'name' => 'currency name',
            'symbol' => 'currency symbol',
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
            'name.required' => 'Enter the currency name, like Euro.',
            'name.string' => 'The :attribute must be text.',
            'name.max' => 'The :attribute can have at most :max characters.',

            'symbol.required' => 'Enter the currency symbol, like €.',
            'symbol.string' => 'The :attribute must be text.',
            'symbol.max' => 'The :attribute can have at most :max characters.',
        ];
    }
}
