<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTransferRequest extends FormRequest
{
    /**
     * Who can update a transfer is decided by the transfer policy, which the controller asks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Friendlier field names for the validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from_account_id' => 'source account',
            'to_account_id' => 'destination account',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $transfer = $this->route('transfer');

        // The user's active accounts, plus the accounts the transfer already uses even if they were deactivated
        $ownAccount = Rule::exists('accounts', 'id')
            ->where('user_id', $this->user()->id)
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', [$transfer->from_account_id, $transfer->to_account_id]));

        return [
            'from_account_id' => ['required', $ownAccount],
            'to_account_id' => ['required', 'different:from_account_id', $ownAccount],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99'],
            'date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Both accounts must use the same currency. The balances are checked by the transfer itself, once it knows the net effect.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from_account_id', 'to_account_id'])) {
                    return;
                }

                $currencies = Account::withTrashed()
                    ->whereIn('id', [$this->integer('from_account_id'), $this->integer('to_account_id')])
                    ->pluck('currency_id')
                    ->unique();

                if ($currencies->count() > 1) {
                    $validator->errors()->add('to_account_id', 'Transfers between accounts in different currencies are not supported.');
                }
            },
        ];
    }
}
