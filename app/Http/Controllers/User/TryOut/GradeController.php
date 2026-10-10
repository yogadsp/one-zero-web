<?php

namespace App\Http\Controllers\User\TryOut;

use App\Charts\SectionGradeChart;
use App\Http\Controllers\Controller;
use App\Models\Exam\Grade;
use App\Services\Contracts\Exam\ExamInterface as ExamService;
use App\Services\Contracts\Exam\GradeInterface as GradeService;
use App\Services\Exam\GradeAnswerReviewService;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    protected $gradeService;

    protected $examService;

    public function __construct(
        GradeService $gradeService,
        ExamService $examService,
        protected GradeAnswerReviewService $gradeAnswerReviewService,
    ) {
        $this->gradeService = $gradeService;
        $this->examService = $examService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return inertia('User/TryOut/Grade/Index', [
            'grades' => $this->gradeService->getAllByUserPaginatedWithParams($request),
        ]);
    }

    public function show($id)
    {
        $grade = Grade::query()
            ->whereKey($id)
            ->where('user_id', auth()->id())
            ->with([
                'category',
                'exam',
                'exam.questionTitle',
                'gradeDetail',
                'gradeDetail.valueCategory',
            ])
            ->firstOrFail();
        $rankingExams = $this->gradeService->getRankingByExam($grade->exam_id);
        $reviewEnabled = (int) $grade->exam->show_answer === 1
            || (int) $grade->exam->questionTitle->show_answer === 1;
        $canReviewAnswers = (int) $grade->is_finished === 1
            && $reviewEnabled
            && (float) $grade->getRawOriginal('grade') >= (float) $grade->exam->questionTitle->minimum_grade;

        // $chart = new SectionGradeChart(new LarapexChart, $grade);

        return inertia('User/TryOut/Grade/Show', [
            'grade' => $grade,
            'rankingExams' => $rankingExams,
            'answers' => $canReviewAnswers ? $this->gradeAnswerReviewService->resolve($grade) : [],
            'reviewEnabled' => $reviewEnabled,
            'canReviewAnswers' => $canReviewAnswers,
        ]);
    }

    /**
     * examShowAnswer
     *
     * @param  mixed  $request
     * @return void
     */
    public function showAnswer($examId, $gradeId)
    {
        if (! $this->examService->find($examId)) {
            return abort('404', 'uppss....');
        }

        $exam = $this->examService->find($examId);
        $grade = Grade::query()
            ->whereKey($gradeId)
            ->where('exam_id', $examId)
            ->where('user_id', auth()->id())
            ->where('is_finished', 1)
            ->firstOrFail();

        $reviewEnabled = (int) $exam->show_answer === 1
            || (int) $exam->questionTitle->show_answer === 1;

        if (! $reviewEnabled) {
            return redirect("/user/grades/{$grade->id}")
                ->with('error', 'Koreksi jawaban belum diaktifkan untuk ujian ini.');
        }

        if ((float) $grade->getRawOriginal('grade') < (float) $exam->questionTitle->minimum_grade) {
            return redirect("/user/grades/{$grade->id}")
                ->with('error', 'Nilai minimum untuk melihat jawaban belum terpenuhi.');
        }

        $answers = $this->gradeAnswerReviewService->resolve($grade);

        return inertia('User/TryOut/Grade/ShowAnswer', [
            'exam' => $exam,
            'grade' => $grade,
            'answers' => $answers,
        ]);
    }

    public function certificate($id)
    {
        $grade = Grade::query()
            ->whereKey($id)
            ->where('user_id', auth()->id())
            ->with(['category', 'exam'])
            ->firstOrFail();
        // return $grade->gradeDetail;
        if ($grade->category->name === 'TOEFL') {
            $pdf = Pdf::loadView('certificate.certificate_toefl', ['grade' => $grade])->setPaper('a4', 'landscape');

            return $pdf->stream();
        } elseif ($grade->category->name === 'TOEIC') {
            $pdf = Pdf::loadView('certificate.certificate_toeic', ['grade' => $grade])->setPaper('a4', 'landscape');

            return $pdf->stream();
        }

    }
}
