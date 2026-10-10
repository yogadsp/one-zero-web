<?php

namespace App\Http\Controllers\User\TryOut;

use App\Http\Controllers\Controller;
use App\Models\Exam\Grade;
use App\Models\Exam\GradeDetail;
use App\Models\Exam\ValueCategoryExam;
// delete soon
use App\Models\Lesson\DetailValueCategory;
use App\Models\Lesson\Question;
use App\Models\Lesson\ValueCategory;
use App\Services\Contracts\Exam\ExamInterface as ExamService;
use App\Services\Contracts\MasterData\CategoryInterface as CategoryService;
use App\Services\Exam\ExamScoringService;
use App\Services\Learning\LearningAccessService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    protected $categoryService;

    protected $examService;

    public function __construct(
        CategoryService $categoryService,
        ExamService $examService,
        protected LearningAccessService $learningAccessService,
        protected ExamScoringService $examScoringService,
    ) {
        $this->categoryService = $categoryService;
        $this->examService = $examService;

        $this->middleware('member', ['only' => ['examStart']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $categoryId)
    {
        if (! $this->categoryService->find($categoryId)) {
            return abort('404', 'uppss....');
        }

        return inertia('User/TryOut/Exam/Index', [
            'exams' => $this->examService->getAllByCategoryPaginatedWithParams($request, $categoryId),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($categoryId, $id)
    {
        if (! $this->categoryService->find($categoryId)) {
            return abort('404', 'uppss....');
        }
        if (! $this->examService->find($id)) {
            return abort('404', 'uppss....');
        }

        //get grade / nilai
        $grade = Grade::where('exam_id', $id)
            ->where('user_id', auth()->user()->id)
            ->first();

        return inertia('User/TryOut/Exam/Show', [
            'exam' => $this->examService->find($id),
            'grade' => $grade,
        ]);
    }

    public function examReset($id)
    {
        if (! $this->examService->find($id)) {
            return abort('404', 'uppss....');
        }

        $exam = $this->examService->find($id);

        Grade::where(['exam_id' => $exam->id, 'user_id' => Auth::user()->id])->delete();

        return redirect()->route('user.categories.lesson-categories.lessons.exams.show', [$exam->category_id, $exam->lesson_category_id, $exam->lesson_id, $exam->id]);
    }

    public function examStart($id, Request $request)
    {
        if (! $this->examService->find($id)) {
            return abort('404', 'uppss....');
        }

        $exam = $this->examService->find($id);

        if (! $this->learningAccessService->canAccess($request->user(), $exam)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke tryout '.strtoupper(strtolower($exam->title)).'. Silakan lakukan pembelian terlebih dahulu.');
        }

        $lastGrade = Grade::query()
            ->where('exam_id', $id)
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->first();
        $gradeOld = empty($lastGrade?->grade_old) ? 0 : $lastGrade->grade_old;

        // if($request->repeat == 1) {
        //     Grade::where('exam_id', $id)->where('user_id', auth()->user()->id)->delete();
        //     GradeDetail::where('exam_id', $id)->where('user_id', auth()->user()->id)->delete();
        // }

        $activeSourceSections = Question::query()
            ->where('question_title_id', $exam->question_title_id)
            ->where('type', 1)
            ->whereNotNull('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => (int) $section)
            ->values();
        $totalSection = $activeSourceSections->count();

        // jika ada ujian yang belum selesai, lanjutkan
        if (! empty($lastGrade) && $lastGrade->is_finished == 0 && ! empty($lastGrade->answers)) {
            return redirect()->route('user.exams.exam-show-questions', [$lastGrade->exam_id, $lastGrade->section]);
        }

        $countGrade = Grade::where('exam_id', $id)->where('user_id', auth()->user()->id)->count();

        // batasi maksimal 5 histori ujian
        if ($countGrade > 5) {
            Grade::where('exam_id', $id)->where('user_id', auth()->user()->id)->orderBy('created_at', 'ASC')->skip(1)->first()->delete();
            GradeDetail::where('exam_id', $id)->where('user_id', auth()->user()->id)->orderBy('created_at', 'ASC')->skip(1)->first()->delete();
        }

        // if(!$grade) {
        $answerInsert = [];

        $firstSourceSection = $activeSourceSections->first();
        $valueExam = ValueCategoryExam::where(['exam_id' => $exam->id, 'section' => $firstSourceSection])->first();

        if ($exam->duration_type == 1) {
            $addMinutes = (int) $exam->duration;
        } elseif ($exam->duration_type == 2 && $valueExam) {
            $addMinutes = $valueExam->duration;
        } elseif ($exam->duration_type == 2) {
            return redirect()->back()->with('error', 'Durasi bagian pertama ujian belum dikonfigurasi oleh admin.');
        } else {
            $addMinutes = 560;
        }

        //create defaul grade
        // Pulihkan attempt kosong yang sempat dibuat ketika soal belum tersedia,
        // tanpa menghapus identitas/history attempt tersebut.
        $grade = ! empty($lastGrade) && $lastGrade->is_finished == 0 ? $lastGrade : new Grade();
        $grade->category_id = $exam->category_id;
        $grade->exam_id = $exam->id;
        $grade->user_id = auth()->user()->id;
        $grade->duration = $addMinutes;
        $grade->total_tolerance = $exam->total_tolerance;
        $grade->section = 1;
        $grade->total_correct = 0;
        $grade->total_section = $totalSection;
        $grade->grade = 0;
        $grade->grade_old = $gradeOld;
        $grade->start_time = Carbon::now();
        $grade->end_time = Carbon::now()->addMinutes($addMinutes)->addSeconds(2);
        $grade->is_finished = 0;
        $grade->number = 0;

        $question_order = 1;

        foreach ($activeSourceSections as $sourceSection) {
            $section = $activeSourceSections->search($sourceSection) + 1;
            if ($exam->random_question == 1) {
                $questions = Question::where('section', $sourceSection)->where('question_title_id', $exam->question_title_id)->with('valueCategory')->inRandomOrder()->get();
            } else {
                $questions = Question::where('section', $sourceSection)->where('question_title_id', $exam->question_title_id)->with('valueCategory')->orderBy('created_at', 'ASC')->get();
            }

            $navigation_order = 0;

            foreach ($questions as $question) {
                if ($question->type == 1) {
                    $navigation_order++;
                }

                $options = [];
                if ($question->option_1 != null) {
                    $options[] = 1;
                }
                if ($question->option_2 != null) {
                    $options[] = 2;
                }
                if ($question->option_3 != null) {
                    $options[] = 3;
                }
                if ($question->option_4 != null) {
                    $options[] = 4;
                }
                if ($question->option_5 != null) {
                    $options[] = 5;
                }

                if ($exam->random_answer == 1) {
                    shuffle($options);
                }

                if (empty($grade->answers)) {
                    $answerInsert[] = [
                        'question_id' => $question->id,
                        'value_category_id' => $question->value_category_id,
                        'value_category_name' => $question->valueCategory->name,
                        'type' => $question->type,
                        'direction' => $question->direction,
                        'question_order' => $question_order,
                        'navigation_order' => $navigation_order,
                        'audio_input' => $question->audio_input,
                        'audio' => $question->audio,
                        'audio_played' => (int) $question->audio_played,
                        'audio_played_limit' => (int) $question->audio_played_limit,
                        'audio_answer_time' => $question->audio_answer_time == null || $question->audio_answer_time == 0 ? 0 : (int) $question->audio_answer_time * 1000,
                        'question' => $question->question,
                        'question_answer' => $question->answer,
                        'option_1' => empty($question->option_1) ? null : $question->option_1,
                        'option_2' => empty($question->option_2) ? null : $question->option_2,
                        'option_3' => empty($question->option_3) ? null : $question->option_3,
                        'option_4' => empty($question->option_4) ? null : $question->option_4,
                        'option_5' => empty($question->option_5) ? null : $question->option_5,
                        'section' => $section,
                        'answer_order' => implode(',', $options),
                        'answer' => 0,
                        'is_correct' => 'N',
                        'option_has_point' => $question->option_has_point,
                        'option_1_point' => is_null($question->option_1_point) ? null : $question->option_1_point,
                        'option_2_point' => is_null($question->option_2_point) ? null : $question->option_2_point,
                        'option_3_point' => is_null($question->option_3_point) ? null : $question->option_3_point,
                        'option_4_point' => is_null($question->option_4_point) ? null : $question->option_4_point,
                        'option_5_point' => is_null($question->option_5_point) ? null : $question->option_5_point,
                        'discussion' => $question->discussion, // pembahasan jawaban
                    ];
                }

                $question_order++;
            }
        }

        if (empty($answerInsert)) {
            return redirect()->back()->with('error', 'Paket ujian belum memiliki soal aktif. Hubungi admin untuk melengkapi bank soal.');
        }

        // Section kosong tidak lagi dianggap wajib. Hanya section yang memiliki soal
        // aktif yang dimasukkan ke sesi ujian dan dinomori ulang secara berurutan.
        $grade->answers = $answerInsert;
        $grade->save();
        // }

        return redirect()->route('user.exams.exam-show-questions', [$grade->exam_id, $grade->section]);
    }

    public function examShowQuestion($id, $section, Request $request)
    {
        if (! $this->examService->find($id)) {
            return abort('404', 'uppss....');
        }

        $exam = $this->examService->find($id);

        if (! $this->learningAccessService->canAccess($request->user(), $exam)) {
            return redirect()->route('user.categories.exams.show', [$exam->category_id, $exam->id])
                ->with('error', 'Akses tryout tidak aktif. Silakan periksa transaksi dan aktivasi voucher Anda.');
        }

        $grade = Grade::where('exam_id', $exam->id)->where('user_id', auth()->user()->id)->orderBy('created_at', 'DESC')->first();

        if (! $grade || $grade->is_finished == 1) {
            return redirect()->route('user.categories.exams.show', [$exam->category_id, $exam->id])
                ->with('error', 'Sesi ujian tidak aktif. Mulai ujian dari halaman paket.');
        }

        if (empty($grade->answers)) {
            return redirect()->route('user.exams.exam-start', $exam->id)
                ->with('error', 'Sesi ujian kosong dipulihkan dari bank soal terbaru.');
        }

        $section = (int) $section;
        if ($section < 1 || $section > (int) $grade->total_section) {
            return redirect()->route('user.exams.exam-show-questions', [$grade->exam_id, $grade->section]);
        }

        // bisa berkali - kali
        // if($grade->is_finished == 1) {
        //     return redirect()->route('user.categories.exams.show', [$grade->category_id, $grade->exam_id]);
        // }

        if ($section != $grade->section && empty($request->nextsection)) {
            return redirect()->route('user.exams.exam-show-questions', [$grade->exam_id, $grade->section]);
        }

        $valueExam = ValueCategoryExam::where(['exam_id' => $exam->id, 'section' => $section])->first();

        if ($exam->duration_type == 1) {
            $addMinutes = (int) $exam->duration;
        } elseif ($exam->duration_type == 2 && $valueExam) {
            $addMinutes = $valueExam->duration;
        } elseif ($exam->duration_type == 2) {
            return redirect()->route('user.categories.exams.show', [$exam->category_id, $exam->id])
                ->with('error', "Durasi bagian {$section} ujian belum dikonfigurasi oleh admin.");
        } else {
            $addMinutes = 560;
        }

        if ($grade->section < $section || $grade->section > $section) {
            if ($grade->section < $section) {
                $grade->section = $grade->section + 1;
            } elseif ($grade->section > $section) {
                $grade->section = $grade->section - 1;
            }

            $grade->number = (int) 0;
            if ($exam->duration_type == 2) {
                $grade->end_time = Carbon::now()->addMinutes($addMinutes)->addSeconds(2);
            }
            $grade->update();
            $grade->refresh();
        }

        $duration = $grade->end_time > Carbon::now() || empty($grade->end_time) ? $grade->end_time->diffInMilliseconds(Carbon::now()) : 0;

        // ensure duration in milliseconds
        // $duration = $exam->duration * 1000;

        $gradeAnswers = $grade['answers'];

        usort($gradeAnswers, function ($a, $b) {
            return $a['section'] <=> $b['section']; // Sort by section in ascending order
        });

        $questionListAll = [];
        $questionCount = 0;

        foreach ($gradeAnswers as $gradeAnswer) {
            $questionListAll[$gradeAnswer['value_category_name']][] = $gradeAnswer;
            $questionCount++;
        }

        $questionLists = array_filter($gradeAnswers, function ($var) use ($section) {
            return $var['section'] == $section;
        });

        $questionLists = empty($questionLists) ? [] : array_values($questionLists);

        // dd($questionLists);

        // dd($questionListAll);

        $totalQuetions = count($questionLists);

        return inertia('User/TryOut/Exam/Question', [
            'exam' => $exam,
            'grade' => $grade,
            'questionLists' => $questionLists,
            'questionListAll' => $questionListAll,
            'questionCount' => $questionCount,
            'duration' => $duration,
            'section' => (int) $grade->section,
            'indexPage' => (int) $grade->number,
            'lastSection' => (int) $grade->total_section,
        ]);
    }

    /**
     * answerQuestion
     *
     * @param  mixed  $request
     * @return void
     */
    public function answerQuestion($examId, Request $request)
    {
        $request->validate([
            'grade_id' => ['required', 'uuid'],
            'question_id' => ['required', 'uuid'],
            'answer' => ['required', 'integer', 'min:1', 'max:5'],
            'number' => ['required', 'integer', 'min:0'],
        ]);

        $grade = Grade::query()
            ->whereKey($request->grade_id)
            ->where('exam_id', $examId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $gradeAnswers = $grade['answers'];
        $index = array_search($request->question_id, array_column($gradeAnswers, 'question_id'));
        abort_if($index === false, 404);

        $gradeAnswers[$index]['is_correct'] = (int) $gradeAnswers[$index]['question_answer'] == (int) $request->answer ? 'Y' : 'N';
        $gradeAnswers[$index]['answer'] = $request->answer;
        $gradeAnswers = $gradeAnswers;

        $grade->update(['answers' => $gradeAnswers, 'number' => $request->number]);

        return $grade;
    }

    /**
     * answerQuestion
     *
     * @param  mixed  $request
     * @return void
     */
    public function audioPlayed($examId, Request $request)
    {
        $request->validate([
            'grade_id' => ['required', 'uuid'],
            'question_id' => ['required', 'uuid'],
        ]);

        $grade = Grade::query()
            ->whereKey($request->grade_id)
            ->where('exam_id', $examId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $gradeAnswers = $grade['answers'];
        $index = array_search($request->question_id, array_column($gradeAnswers, 'question_id'));
        abort_if($index === false, 404);
        $gradeAnswers[$index]['audio_played'] = $gradeAnswers[$index]['audio_played'] + 1;
        $gradeAnswers = $gradeAnswers;
        $grade->update(['answers' => $gradeAnswers]);

        return $grade;
    }

    /**
     * decrementTolerance
     *
     * @param  mixed  $request
     * @return void
     */
    public function decrementTolerance($examId, Request $request)
    {
        $request->validate(['grade_id' => ['required', 'uuid']]);

        $grade = Grade::query()
            ->whereKey($request->grade_id)
            ->where('exam_id', $examId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $total_tolerance = $grade->total_tolerance > 0 ? $grade->total_tolerance - 1 : 0;
        $is_blocked = $total_tolerance <= 0 ? 1 : 0;
        $grade->update(['total_tolerance' => $total_tolerance, 'is_blocked' => $is_blocked]);
    }

    /**
     * endExam
     *
     * @param  mixed  $request
     * @return void
     */
    public function endExam($examId, Request $request)
    {
        if (! $this->examService->find($examId)) {
            return abort('404', 'uppss....');
        }

        $exam = $this->examService->find($examId);

        //update nilai di table grades
        $request->validate(['grade_id' => ['required', 'uuid']]);

        $grade = Grade::query()
            ->whereKey($request->grade_id)
            ->where('exam_id', $exam->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $gradeAnswers = $grade['answers'];

        $overallScore = $this->examScoringService->calculateOverall(
            $gradeAnswers,
            (int) $exam->questionTitle->assessment_type,
        );
        $previousGrade = (float) str_replace(',', '', (string) $grade->grade_old);
        $usesCategoryScoring = (int) $exam->questionTitle->add_value_category === 1
            && (int) $exam->questionTitle->assessment_type === 1;
        $overallGrade = $grade->is_blocked == 1 ? 0 : $overallScore['score'];

        $grade->end_time = Carbon::now();
        $grade->total_correct = $overallScore['total_correct'];
        $grade->grade = $overallGrade;
        if (! $usesCategoryScoring && $previousGrade <= 0) {
            $grade->grade_old = $overallGrade;
        }
        $grade->is_finished = 1;
        $grade->update();

        $valueCategories = ValueCategory::where('category_id', $exam->category_id)->orderBy('section', 'ASC')->get();

        if ($usesCategoryScoring) {
            $result = [];
            $finalGradeExam = 0;

            foreach ($valueCategories as $valueCategory) {
                $categoryScore = $this->examScoringService->calculateCategory($gradeAnswers, $valueCategory);
                $finalGrade = $categoryScore['score'];

                $conversion = DetailValueCategory::query()
                    ->where('value_category_id', $valueCategory->id)
                    ->where('min_grade', '<=', $categoryScore['total_correct'])
                    ->where('max_grade', '>=', $categoryScore['total_correct'])
                    ->first();

                if ($conversion) {
                    $finalGrade = (float) $conversion->final_grade;
                }

                $result[] = [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'category_id' => $exam->category_id,
                    'exam_id' => $exam->id,
                    'user_id' => auth()->user()->id,
                    'value_category_id' => $valueCategory->id,
                    'grade_id' => $grade->id,
                    'total_correct' => $grade->is_blocked == 1 ? 0 : $categoryScore['total_correct'],
                    'total_wrong' => $grade->is_blocked == 1 ? 0 : $categoryScore['total_wrong'],
                    'grade' => $grade->is_blocked == 1 ? 0 : $finalGrade,
                ];

                $finalGradeExam += $grade->is_blocked == 1 ? 0 : $finalGrade;
            }

            if (strtoupper((string) $exam->category?->name) === 'TOEFL' && $valueCategories->count() > 0) {
                $finalGradeExam = round(($finalGradeExam / 3) * 10);
            }

            GradeDetail::where('grade_id', $grade->id)->delete();
            GradeDetail::insert($result);

            $grade->grade = $finalGradeExam;
            if ($previousGrade <= 0) {
                $grade->grade_old = $finalGradeExam;
            }
            $grade->update();
        }

        //redirect hasil
        return redirect()->route('user.categories.exams.show', [$exam->category_id, $exam->id]);
    }
}
