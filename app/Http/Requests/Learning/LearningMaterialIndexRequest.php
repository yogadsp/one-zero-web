<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningMaterialIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->level === 1;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['live_class', 'video'])],
        ];
    }
}
