<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'details' => ['required', 'string', 'max:255'],
            'balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
            'min_balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
        ];
    }
}
