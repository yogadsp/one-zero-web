<?php

namespace App\Http\Requests\Lesson;

use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends FormRequest
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
        $questionTitle = QuestionTitle::find($this->input('question_title_id'));

        $validation = [
            'question_title_id' => ['required', 'uuid', 'exists:question_titles,id'],
            'type' => ['required', 'integer', Rule::in([1, 2])],
            'value_category_id' => [
                'required',
                'uuid',
                Rule::exists('value_categories', 'id')->where(
                    fn ($query) => $query->where('category_id', $questionTitle?->category_id)->whereNull('deleted_at')
                ),
            ],
            'audio_input' => ['required_if:type,1', 'nullable', 'boolean'],
            'option_has_point' => ['required_if:type,1', 'nullable', 'boolean'],
            'question' => ['required_if:type,1', 'nullable', 'string'],
            'discussion' => ['nullable', 'string'],
            'direction' => ['required_if:type,2', 'nullable', 'string'],
        ];

        if ($questionTitle && (int) $this->input('type') === 1) {
            $validation['answer'] = ['required', 'integer', 'min:1', 'max:'.$questionTitle->total_choices];
            $valueCategory = ValueCategory::find($this->input('value_category_id'));
            $requiresPoints = (int) $valueCategory?->assessment_type === ValueCategory::ASSESSMENT_OPTION_POINTS;

            if ($requiresPoints) {
                $validation['option_has_point'] = ['accepted'];
            }

            for ($i = 1; $i <= (int) $questionTitle->total_choices; $i++) {
                $validation['option_'.$i] = ['required', 'string'];
                $validation['option_'.$i.'_point'] = [
                    Rule::requiredIf($requiresPoints || $this->boolean('option_has_point')),
                    'nullable', 'numeric', 'min:0', 'max:1000',
                ];
            }
        }

        return $validation;
    }

    public function attributes()
    {
        return [
            'value_category_id' => __('question.value_category_id'),
            'question' => __('question.question'),
            'option_1' => __('question.option_1'),
            'option_2' => __('question.option_2'),
            'option_3' => __('question.option_3'),
            'option_4' => __('question.option_4'),
            'option_5' => __('question.option_5'),
            'answer' => __('question.answer'),
            'section' => __('question.section'),
            'option_has_point' => 'penggunaan poin pilihan',
        ];
    }
}
