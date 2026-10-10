<?php

namespace App\Http\Requests\Learning;

use App\Models\Learning\LearningMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->level === 1;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'type' => ['required', Rule::in(LearningMaterial::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'description' => ['nullable', 'string', 'max:5000'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'starts_at' => [
                Rule::requiredIf(fn () => $this->input('type') === LearningMaterial::TYPE_LIVE_CLASS),
                'nullable',
                'date',
            ],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_published' => ['required', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
