<?php

namespace Tests\Unit;

use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use App\Services\Exam\ExamProgramProfileService;
use App\Services\Exam\ExamScoringService;
use PHPUnit\Framework\TestCase;

class ExamScoringServiceTest extends TestCase
{
    public function test_cpns_option_points_are_summed_even_when_an_option_is_not_marked_correct(): void
    {
        $category = new ValueCategory([
            'id' => 'category-tkp',
            'assessment_type' => ValueCategory::ASSESSMENT_OPTION_POINTS,
        ]);

        $result = (new ExamScoringService())->calculateCategory([
            [
                'type' => 1,
                'value_category_id' => 'category-tkp',
                'answer' => 2,
                'is_correct' => 'N',
                'option_2_point' => 4,
            ],
            [
                'type' => 1,
                'value_category_id' => 'category-tkp',
                'answer' => 5,
                'is_correct' => 'Y',
                'option_5_point' => 5,
            ],
            [
                'type' => 2,
                'value_category_id' => 'category-tkp',
                'answer' => 1,
                'is_correct' => 'Y',
                'option_1_point' => 99,
            ],
        ], $category);

        $this->assertSame(1, $result['total_correct']);
        $this->assertSame(1, $result['total_wrong']);
        $this->assertSame(2, $result['question_count']);
        $this->assertSame(9.0, $result['score']);
    }

    public function test_overall_scoring_supports_percentage_correct_points_and_option_points(): void
    {
        $answers = [
            ['type' => 1, 'answer' => 1, 'is_correct' => 'Y', 'option_1_point' => 5],
            ['type' => 1, 'answer' => 2, 'is_correct' => 'N', 'option_2_point' => 3],
            ['type' => 1, 'answer' => 0, 'is_correct' => 'N'],
        ];
        $service = new ExamScoringService();

        $this->assertSame(33.33, $service->calculateOverall($answers, QuestionTitle::ASSESSMENT_OVERALL_PERCENTAGE)['score']);
        $this->assertSame(5.0, $service->calculateOverall($answers, QuestionTitle::ASSESSMENT_CORRECT_POINTS)['score']);
        $this->assertSame(8.0, $service->calculateOverall($answers, QuestionTitle::ASSESSMENT_OPTION_POINTS)['score']);
        $this->assertSame(0.0, $service->calculateOverall([], QuestionTitle::ASSESSMENT_OVERALL_PERCENTAGE)['score']);
    }

    public function test_program_profiles_restrict_scoring_and_keep_structured_sections(): void
    {
        $service = new ExamProgramProfileService();

        $cpns = $service->profileForName('cpns');
        $utbk = $service->profileForName('UTBK');
        $tka = $service->profileForName('TKA');

        $this->assertSame([ValueCategory::ASSESSMENT_OPTION_POINTS], array_column($cpns['value_assessment_options'], 'value'));
        $this->assertCount(2, $utbk['duration_options']);
        $this->assertStringContainsString('tujuh subtes', $utbk['section_help']);
        $this->assertStringContainsString('mata uji', $tka['section_help']);
    }
}
