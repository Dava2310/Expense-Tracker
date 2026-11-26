<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the form that registers an income.
 */
class StoreIncomeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules for an income; amounts have at most two decimals because they are stored as cents.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99'],
            'concept' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            // Only the user's own, active accounts can receive an income
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)->whereNull('deleted_at'),
            ],
            // Only the user's own categories can be used
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
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
            'amount' => 'amount',
            'concept' => 'concept',
            'date' => 'date',
            'account_id' => 'account',
            'category_id' => 'category',
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
            'amount.required' => 'Enter the amount.',
            'amount.numeric' => 'Enter the :attribute as a number, like 25.50.',
            'amount.gt' => 'Enter an amount greater than 0.',
            'amount.decimal' => 'Use at most 2 decimals in the :attribute, like 25.50.',
            'amount.max' => 'The :attribute can’t be more than :max.',

            'concept.required' => 'Enter a concept, like “Monthly salary”.',
            'concept.string' => 'The :attribute must be text.',
            'concept.max' => 'The :attribute can have at most :max characters.',

            'date.required' => 'Choose the date.',
            'date.date_format' => 'Enter the date as year-month-day, like 2026-11-25.',

            'account_id.required' => 'Choose the account the income is added to.',
            'account_id.exists' => 'Choose one of your active accounts.',

            'category_id.required' => 'Choose a category.',
            'category_id.exists' => 'Choose one of your own categories.',
        ];
    }
}
