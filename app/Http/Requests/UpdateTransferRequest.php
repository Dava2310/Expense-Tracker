<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the form that edits a transfer.
 */
class UpdateTransferRequest extends StoreTransferRequest
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
     * Both accounts must share a currency.
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
