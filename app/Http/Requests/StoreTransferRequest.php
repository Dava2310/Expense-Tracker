<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
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
        // Only the user's own, active accounts can take part in a transfer
        $ownActiveAccount = Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)->whereNull('deleted_at');

        return [
            'from_account_id' => ['required', $ownActiveAccount],
            'to_account_id' => ['required', 'different:from_account_id', $ownActiveAccount],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99'],
            'date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
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
     * Both accounts must use the same currency and the source must have enough balance.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from_account_id', 'to_account_id', 'amount'])) {
                    return;
                }

                $from = $this->user()->accounts()->find($this->integer('from_account_id'));
                $to = $this->user()->accounts()->find($this->integer('to_account_id'));

                if ($from->currency_id !== $to->currency_id) {
                    $validator->errors()->add('to_account_id', 'Transfers between accounts in different currencies are not supported.');

                    return;
                }

                if (! $from->hasEnoughBalanceFor($this->input('amount'))) {
                    $validator->errors()->add(
                        'amount',
                        $from->name.' only has '.$from->formatMoney($from->balance).' available.'
                    );
                }
            },
        ];
    }
}
