<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        abort_unless($this->route('account')->user_id === $this->user()->id, 404);

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
