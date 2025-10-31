<?php

namespace App\Repositories\Exam;

use App\Models\Exam\Grade;
use App\Repositories\Contracts\Exam\GradeInterface;
use App\Repositories\BaseRepository;

class GradeRepository extends BaseRepository implements GradeInterface
{
    /**
     * @var
     */
    protected $model;

    public function __construct(Grade $grade)
    {
        $this->model = $grade;
    }

    public function getAllByUserPaginatedWithParams($params, $limit = 10)
    {
        $exams = $this->model->select(
            'id',
            'created_at',
            'updated_at',
            'category_id',
            'exam_id',
            'user_id',
            'duration',
            'number',
            'section',
            'total_section',
            'start_time',
            'end_time',
            'total_correct',
            'grade_old',
            'grade',
            'is_finished',
            'total_tolerance',
            'is_blocked'
        );
        $exams = $exams->where('is_finished', 1)->where('user_id', auth()->user()->id)->with(['category', 'exam'])->orderBy('created_at', 'DESC')->paginate($limit);
        return $exams;
    }

    public function getSummaryExamByUser($limit = 5)
    {
        $exams = $this->model->select(
            'id',
            'created_at',
            'updated_at',
            'category_id',
            'exam_id',
            'user_id',
            'duration',
            'number',
            'section',
            'total_section',
            'start_time',
            'end_time',
            'total_correct',
            'grade_old',
            'grade',
            'is_finished',
            'total_tolerance',
            'is_blocked'
        );
        $exams = $exams->where('is_finished', 1)->where('user_id', auth()->user()->id)->with(['category', 'exam'])->orderBy('created_at', 'DESC')->limit($limit)->get();
        return $exams;
    }

    public function find($id)
    {
        return $this->model->select(
            'id',
            'created_at',
            'updated_at',
            'category_id',
            'exam_id',
            'user_id',
            'duration',
            'number',
            'section',
            'total_section',
            'start_time',
            'end_time',
            'total_correct',
            'grade_old',
            'grade',
            'is_finished',
            'total_tolerance',
            'is_blocked'
        )->with([
            'category',
            'exam',
            'gradeDetail',
            'gradeDetail.valueCategory',
            // 'exam.questionTitle.question',
            'exam.questionTitle'
        ])->find($id);
    }

    public function getRankingByExam($examId, $limit = 10)
    {
        $grades = $this->model
            ->select(
                'id',
                'created_at',
                'updated_at',
                'category_id',
                'exam_id',
                'user_id',
                'duration',
                'number',
                'section',
                'total_section',
                'start_time',
                'end_time',
                'total_correct',
                'grade_old',
                'grade',
                'is_finished',
                'total_tolerance',
                'is_blocked'
            )->where(['exam_id' => $examId, 'is_finished' => 1])->with(['user.student', 'user.student.province', 'user.student.city',])->orderBy('grade_old', 'DESC')->paginate($limit);
        return $grades;
    }
}
