<?php

namespace App\Http\Requests\Lesson;

use App\Services\Exam\ExamProgramProfileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValueCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $profileService = app(ExamProgramProfileService::class);
        $categoryId = $this->input('category_id');

        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:191'],
            'assessment_type' => ['required', 'integer', Rule::in($profileService->allowedValueAssessmentTypes($categoryId))],
            'section' => [
                'required',
                'integer',
                'min:1',
                'max:100',
                Rule::unique('value_categories', 'section')
                    ->where(fn ($query) => $query->where('category_id', $categoryId)->whereNull('deleted_at'))
                    ->ignore($this->route('value_category')),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'category_id' => __('value_category.category_id'),
            'name' => __('value_category.name'),
            'assessment_type' => __('value_category.assessment_type'),
            'section' => __('value_category.section'),
        ];
    }
}
