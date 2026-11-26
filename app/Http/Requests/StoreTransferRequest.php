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
     * The plain-words names of the fields, used by the messages of both the create and the edit form.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from_account_id' => 'source account',
            'to_account_id' => 'destination account',
            'amount' => 'amount',
            'date' => 'date',
            'note' => 'note',
        ];
    }

    /**
     * Messages written for people, shared with the edit form (UpdateTransferRequest extends this class).
     * The messages for different currencies and for a source without enough balance are written in after().
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from_account_id.required' => 'Choose the account the money comes from.',
            'from_account_id.exists' => 'Choose one of your active accounts.',

            'to_account_id.required' => 'Choose the account the money goes to.',
            'to_account_id.different' => 'Choose a different account from the source account.',
            'to_account_id.exists' => 'Choose one of your active accounts.',

            'amount.required' => 'Enter the amount.',
            'amount.numeric' => 'Enter the :attribute as a number, like 25.50.',
            'amount.gt' => 'Enter an amount greater than 0.',
            'amount.decimal' => 'Use at most 2 decimals in the :attribute, like 25.50.',
            'amount.max' => 'The :attribute can’t be more than :max.',

            'date.required' => 'Choose the date.',
            'date.date_format' => 'Enter the date as year-month-day, like 2026-11-25.',

            'note.string' => 'The :attribute must be text.',
            'note.max' => 'The :attribute can have at most :max characters.',
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
