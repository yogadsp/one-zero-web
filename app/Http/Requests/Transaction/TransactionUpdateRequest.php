<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->level === 1;
    }

    public function rules(): array
    {
        return [
            'transaction_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'done'])],
        ];
    }
}
