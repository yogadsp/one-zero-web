<?php

namespace App\Services\Exam;

use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;

class ExamScoringService
{
    public function calculateOverall(array $answers, int $assessmentType): array
    {
        $questions = $this->scoredQuestions($answers);
        $correct = array_values(array_filter($questions, fn (array $answer) => ($answer['is_correct'] ?? 'N') === 'Y'));

        $score = match ($assessmentType) {
            QuestionTitle::ASSESSMENT_CORRECT_POINTS => array_sum(array_map(
                fn (array $answer) => $this->selectedOptionPoint($answer, 1),
                $correct,
            )),
            QuestionTitle::ASSESSMENT_OPTION_POINTS => array_sum(array_map(
                fn (array $answer) => $this->selectedOptionPoint($answer),
                $questions,
            )),
            default => $this->percentage(count($correct), count($questions)),
        };

        return [
            'total_correct' => count($correct),
            'total_wrong' => max(count($questions) - count($correct), 0),
            'score' => round($score, 2),
        ];
    }

    public function calculateCategory(array $answers, ValueCategory $valueCategory): array
    {
        $questions = array_values(array_filter(
            $this->scoredQuestions($answers),
            fn (array $answer) => ($answer['value_category_id'] ?? null) === $valueCategory->id,
        ));
        $correct = array_values(array_filter($questions, fn (array $answer) => ($answer['is_correct'] ?? 'N') === 'Y'));

        $score = match ((int) $valueCategory->assessment_type) {
            ValueCategory::ASSESSMENT_CORRECT_COUNT => count($correct),
            ValueCategory::ASSESSMENT_OPTION_POINTS => array_sum(array_map(
                fn (array $answer) => $this->selectedOptionPoint($answer),
                $questions,
            )),
            default => $this->percentage(count($correct), count($questions)),
        };

        return [
            'total_correct' => count($correct),
            'total_wrong' => max(count($questions) - count($correct), 0),
            'question_count' => count($questions),
            'score' => round($score, 2),
        ];
    }

    private function scoredQuestions(array $answers): array
    {
        return array_values(array_filter($answers, fn (array $answer) => (int) ($answer['type'] ?? 0) === 1));
    }

    private function selectedOptionPoint(array $answer, float $fallback = 0): float
    {
        $selected = (int) ($answer['answer'] ?? 0);

        if ($selected < 1 || $selected > 5) {
            return 0;
        }

        $point = $answer["option_{$selected}_point"] ?? null;

        return is_numeric($point) ? (float) $point : $fallback;
    }

    private function percentage(int $correct, int $total): float
    {
        return $total > 0 ? ($correct / $total) * 100 : 0;
    }
}
