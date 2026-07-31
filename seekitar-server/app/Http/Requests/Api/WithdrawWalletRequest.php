<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'      => ['required', 'numeric', 'min:10000'],
            'bank_account' => ['required', 'string', 'max:50'],
            'bank_name'    => ['required', 'string', 'max:100'],
        ];
    }
}
