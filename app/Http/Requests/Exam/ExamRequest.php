<?php

namespace App\Http\Requests\Exam;

use App\Services\Content\RichTextSanitizer;
use App\Services\Exam\ExamProgramProfileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('description'))) {
            $this->merge([
                'description' => app(RichTextSanitizer::class)->sanitize($this->input('description')),
            ]);
        }
    }

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
            'question_title_id' => [
                'required',
                'uuid',
                Rule::exists('question_titles', 'id')->where(fn ($query) => $query->where('category_id', $categoryId)->whereNull('deleted_at')),
            ],
            'title' => ['required', 'string', 'max:191'],
            'duration_type' => ['required', 'integer', Rule::in($profileService->allowedDurationTypes($categoryId))],
            'duration' => ['nullable', 'required_if:duration_type,1', 'integer', 'min:1', 'max:1440'],
            'duration_section' => ['nullable', 'required_if:duration_type,2', 'array'],
            'duration_section.*' => ['required_if:duration_type,2', 'integer', 'min:1', 'max:1440'],
            'description' => ['required', 'string', 'max:50000'],
            'random_question' => ['required', 'boolean'],
            'random_answer' => ['required', 'boolean'],
            'show_answer' => ['required', 'boolean'],
            'show_question_number_navigation' => ['required', 'boolean'],
            'show_question_number' => ['required', 'boolean'],
            'next_question_automatically' => ['required', 'boolean'],
            'show_prev_next_button' => ['required', 'boolean'],
            'type_option' => ['required', 'integer', Rule::in([1, 2])],
            'price' => ['required', 'numeric', 'min:0'],
            'total_tolerance' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function attributes()
    {
        return [
            'category_id' => __('exam.category_id'),
            'question_title_id' => __('exam.question_title_id'),
            'title' => __('exam.title'),
            'duration' => __('exam.duration'),
            'description' => __('exam.description'),
            'random_question' => __('exam.random_question'),
            'random_answer' => __('exam.random_answer'),
            'show_answer' => __('exam.show_answer'),
            'show_question_number_navigation' => __('exam.show_question_number_navigation'),
            'show_question_number' => __('exam.show_question_number'),
            'next_question_automatically' => __('exam.next_question_automatically'),
            'show_prev_next_button' => __('exam.show_prev_next_button'),
            'type_option' => __('exam.type_option'),
            'price' => __('exam.price'),
            'total_tolerance' => __('exam.total_tolerance'),
        ];
    }
}
