<?php

namespace App\Http\Controllers\User\TryOut;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\Exam\GradeInterface as GradeService;
use App\Services\Contracts\Exam\ExamInterface as ExamService;
use App\Charts\SectionGradeChart;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Exam\Grade;

class GradeController extends Controller
{
    protected $gradeService, $examService;

    public function __construct(GradeService $gradeService, ExamService $examService)
    {
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
            'grades' => $this->gradeService->getAllByUserPaginatedWithParams($request)
        ]);
    }

    public function show($id)
    {
        if(!$this->gradeService->find($id)) return abort('404', 'uppss....');

        $grade = $this->gradeService->find($id);
        $rankingExams = $this->gradeService->getRankingByExam($grade->exam_id);

        // $chart = new SectionGradeChart(new LarapexChart, $grade);

        return inertia('User/TryOut/Grade/Show', [
            'grade' => $grade,
            'rankingExams' => $rankingExams,
            'answers' => empty($grade->answers) ? [] : $grade->answers,
        ]);
    }

    /**
     * examShowAnswer
     *
     * @param  mixed $request
     * @return void
     */
    public function showAnswer($examId, $gradeId) 
    {
        // check exam exist
        if(!$this->examService->find($examId)) return abort('404', 'uppss....');
        $exam = $this->examService->find($examId);
        $grade = Grade::where('id', $gradeId)->where('user_id', auth()->user()->id)->orderBy('created_at', 'DESC')->first();
        if(!$grade) return abort('404', 'uppss....');
        // if($section != $grade->section && empty($request->nextsection)) {
        //     return redirect()->route('user.exams.exam-show-questions', [$grade->exam_id, 1, $grade->section]);
        // }

        // $section = $section;

        // $valueExam = ValueCategoryExam::where(['exam_id' => $exam->id, 'section' => $section])->first();

        // if($exam->duration_type == 1) {
        //     $addMinutes = (int) $exam->duration;
        // } else if($exam->duration_type == 2) {
        //     $addMinutes = $valueExam->duration;
        // } else {
        //     $addMinutes = 560;
        // }

        // if($grade->section < $section || $grade->section > $section) {
        //     if($grade->section < $section) {
        //         $grade->section = $grade->section + 1;
        //     } else if($grade->section > $section) {
        //         $grade->section = $grade->section - 1;
        //     }

        //     $grade->number = (int) 0;
        //     if($exam->duration_type == 2) {
        //         $grade->end_time = Carbon::now()->addMinutes($addMinutes)->addSeconds(2);
        //     }
        //     // $grade->update();
        //     // $grade->refresh();
        // }

        // $duration = $grade->end_time > Carbon::now() || empty($grade->end_time) ? $grade->end_time->diffInMilliseconds(Carbon::now()) : 0;

        // ensure duration in milliseconds
        // $duration = $exam->duration * 1000;

        $gradeAnswers = $grade['answers'];

        usort($gradeAnswers, function($a, $b) {
            return $a['section'] <=> $b['section']; // Sort by section in ascending order
        });

        $questionListAll = [];

        foreach($gradeAnswers as $gradeAnswer) {
            $questionListAll[$gradeAnswer['value_category_name']][] = $gradeAnswer;
        }

        $section = 1;

        $questionLists = array_filter($gradeAnswers, function ($var) use($section) {
            return ($var['section'] == $section);
        });
        
        $questionLists = empty($questionLists) ? [] : array_values($questionLists);
        
        // dd($questionLists);

        // dd($questionListAll);


        $totalQuetions = count($questionLists);

        return inertia('User/TryOut/Grade/ShowAnswer', [
            'exam' => $exam,
            'grade' => $grade,
            'questionLists' => $questionLists,
            'questionListAll' => $questionListAll,
            // 'duration' => $duration,
            'section' => (int) $grade->section,
            'indexPage' => (int) $grade->number,
            'lastSection' => (int) $grade->total_section,
        ]);
    }

    public function certificate($id)
    {
        $grade = $this->gradeService->find($id);
        if(!$grade) return abort('404', 'uppss....');
        // return $grade->gradeDetail;
        if($grade->category->name === "TOEFL"){
            $pdf = Pdf::loadView('certificate.certificate_toefl', ['grade' => $grade])->setPaper('a4', 'landscape');
            return $pdf->stream();
        }elseif ($grade->category->name === "TOEIC") {
            $pdf = Pdf::loadView('certificate.certificate_toeic', ['grade' => $grade])->setPaper('a4', 'landscape');
            return $pdf->stream();
        }
        
    }    
}
