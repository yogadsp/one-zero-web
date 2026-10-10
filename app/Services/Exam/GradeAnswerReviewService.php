<?php

namespace App\Services\Exam;

use App\Models\Exam\Grade;
use App\Models\Lesson\Question;

class GradeAnswerReviewService
{
    /**
     * Keep the submitted answer snapshot immutable, but use the current
     * question discussion as the authoritative explanation when available.
     */
    public function resolve(Grade $grade): array
    {
        $answers = collect($grade->answers ?? [])->values();
        $questionIds = $answers
            ->pluck('question_id')
            ->filter()
            ->unique()
            ->values();

        $currentDiscussions = Question::withTrashed()
            ->whereIn('id', $questionIds)
            ->pluck('discussion', 'id');

        $reviewNumber = 0;

        return $answers
            ->sortBy([
                ['section', 'asc'],
                ['question_order', 'asc'],
            ])
            ->values()
            ->map(function (array $answer) use ($currentDiscussions, &$reviewNumber) {
                if ((int) ($answer['type'] ?? 0) === 1) {
                    $reviewNumber++;
                }

                $currentDiscussion = $currentDiscussions->get($answer['question_id'] ?? null);
                $answer['discussion'] = filled($currentDiscussion)
                    ? $currentDiscussion
                    : ($answer['discussion'] ?? null);
                $answer['review_number'] = (int) ($answer['type'] ?? 0) === 1 ? $reviewNumber : null;

                return $answer;
            })
            ->all();
    }
}
