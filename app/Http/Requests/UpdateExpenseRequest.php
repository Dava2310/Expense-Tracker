<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    /**
     * Who can update an expense is decided by the expense policy, which the controller asks.
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
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99'],
            'concept' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            // The user's active accounts, plus the account the expense already belongs to even if it was deactivated
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $this->route('expense')->account_id)),
            ],
            // Only the user's own categories can be used
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    /**
     * Not the balance check of StoreExpenseRequest: on an edit it runs in the controller, after the authorization check.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [];
    }
}
