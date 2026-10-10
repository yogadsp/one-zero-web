<?php

namespace App\Http\Requests\Learning;

use App\Services\Learning\LearningCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(LearningCatalogService::TYPES)],
            'category' => ['nullable', 'uuid', 'exists:categories,id'],
        ];
    }
}
