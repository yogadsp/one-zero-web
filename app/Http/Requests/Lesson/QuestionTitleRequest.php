<?php

namespace App\Http\Requests\Lesson;

use App\Services\Exam\ExamProgramProfileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionTitleRequest extends FormRequest
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
            'total_choices' => ['required', 'integer', 'min:2', 'max:5'],
            'total_section' => ['required', 'integer', 'min:1', 'max:100'],
            'add_value_category' => ['required', 'boolean'],
            'assessment_type' => [
                'nullable',
                'required_if:add_value_category,1',
                'integer',
                Rule::in($profileService->allowedQuestionAssessmentTypes($categoryId)),
            ],
            'show_answer' => ['required', 'boolean'],
            'minimum_grade' => ['nullable', 'required_if:show_answer,1', 'numeric', 'min:0'],
        ];
    }

    public function attributes()
    {
        return [
            'category_id' => __('question_title.category_id'),
            'name' => __('question_title.name'),
            'total_choices' => __('question_title.total_choices'),
            'total_section' => __('question_title.total_section'),
            'add_value_category' => __('question_title.add_value_category'),
            'assessment_type' => __('question_title.assessment_type'),
            'show_answer' => __('question_title.show_answer'),
            'minimum_grade' => __('question_title.minimum_grade'),
        ];
    }
}
