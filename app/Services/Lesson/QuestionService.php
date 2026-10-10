<?php

namespace App\Services\Lesson;

use App\Repositories\Contracts\Lesson\QuestionInterface as QuestionRepo;
use App\Services\Contracts\Lesson\QuestionInterface;
use App\Models\Lesson\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Auth;
use Ramsey\Uuid\Uuid;
use App\Traits\Uploadable;
use App\Models\Lesson\ValueCategory;
use App\Services\Content\EditorImageStorageService;

class QuestionService implements QuestionInterface
{
    use Uploadable;

    protected $questionRepo;

    protected $image_path = 'upload_files/questions';

    public function __construct(QuestionRepo $questionRepo, protected EditorImageStorageService $editorImageStorage)
    {
        $this->questionRepo = $questionRepo;
    }

    public function all($columns = ['*'])
    {
        return $this->questionRepo->all($columns);
    }

    public function getAllSimplePaginatedWithParams($params, $limit = 10)
    {
       return $this->questionRepo->getAllSimplePaginatedWithParams($params, $limit);
    }

    public function getByQuestionTitlePaginatedWithParams($params, $questionTitleId, $limit = 10)
    {
        return $this->questionRepo->getByQuestionTitlePaginatedWithParams($params, $questionTitleId, $limit);
    }

    public function getAllPaginatedWithParams($params, $limit = 10)
    {
       return $this->questionRepo->getAllPaginatedWithParams($params, $limit);
    }

     /**
     * Find data by id
     *
     * @param $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id)
    {
        return $this->questionRepo->find($id);
    }

    /**
     * @param $request
     *
     * @return mixed
     * @throws \Throwable
     */
    public function create($request)
    {
        $permissions = DB::transaction(function () use ($request) {
            $input = $request->all();
            $input['audio_played'] = 0;
            $input['direction'] = $request->type == 1 ? null : $this->persistEditorHtml($request->direction, $request);
            $input['audio_input'] = $request->type == 2 ? null : $request->audio_input;
            $input['audio_played_limit'] = $request->audio_input == 0 || $request->type == 2 ? null : $request->audio_played_limit;
            $input['audio_played_limit'] = $request->audio_input == 0 || $request->type == 2 ? null : $request->audio_played_limit;
            $input['audio_answer_time'] = $request->audio_input == 0 || $request->type == 2 ? null : $request->audio_answer_time;
            $input['section'] = ValueCategory::find($request->value_category_id)->section;
            $input['question'] = empty($request->question) || $request->type == 2 ? 0 : $this->persistEditorHtml($request->question, $request);
            $input['option_1'] = empty($request->option_1) || $request->type == 2 ? 0 : $this->persistEditorHtml($request->option_1, $request);
            $input['option_2'] = empty($request->option_2) || $request->type == 2 ? 0 : $this->persistEditorHtml($request->option_2, $request);
            $input['option_3'] = empty($request->option_3) || $request->type == 2 ? 0 : $this->persistEditorHtml($request->option_3, $request);
            $input['option_4'] = empty($request->option_4) || $request->type == 2 ? 0 : $this->persistEditorHtml($request->option_4, $request);
            $input['option_5'] = empty($request->option_5) || $request->type == 2 ? null : $this->persistEditorHtml($request->option_5, $request);
            $input['answer'] = $request->type == 2 ? null : $request->answer;
            $input['discussion'] = $request->type == 2 ? null : $this->persistEditorHtml($request->discussion, $request);


            if($request->hasFile('audio')){
                $file = $request->file('audio')->getClientOriginalName();
                $filename = pathinfo($file, PATHINFO_FILENAME);

                $filename = $this->uploadFile($request->file('audio'), $filename, $this->image_path);
                $input['audio'] = $filename;
            }

            return $this->questionRepo->create($input);
        });

        return $permissions;
    }

     /**
     * @param $id
     * @param $request
     *
     * @return mixed
     * @throws \Throwable
    */
    public function update($request, $id)
    {
        $permissions = DB::transaction(function () use ($request, $id) {
            $input = $request->except('_token','_method');
            $question = $this->find($id);
            $input['audio_input'] = $request->type == 2 ? null : $request->audio_input;
            $input['audio_played'] = 0;
            $input['direction'] = $request->type == 1 ? null : $this->persistEditorHtml($request->direction, $request);
            $input['audio_played_limit'] = $request->audio_input == 0 || $request->type == 2 ? null : $request->audio_played_limit;
            $input['audio_played_limit'] = $request->audio_input == 0 || $request->type == 2 ? null : $request->audio_played_limit;
            $input['audio_answer_time'] = $request->audio_input == 0 || $request->type == 2 ? null : $request->audio_answer_time;
            $input['section'] = ValueCategory::find($request->value_category_id)->section;
            $input['question'] = empty($request->question) || $request->type == 2 ? null : $this->persistEditorHtml($request->question, $request);
            $input['option_1'] = empty($request->option_1) || $request->type == 2 ? null : $this->persistEditorHtml($request->option_1, $request);
            $input['option_2'] = empty($request->option_2) || $request->type == 2 ? null : $this->persistEditorHtml($request->option_2, $request);
            $input['option_3'] = empty($request->option_3) || $request->type == 2 ? null : $this->persistEditorHtml($request->option_3, $request);
            $input['option_4'] = empty($request->option_4) || $request->type == 2 ? null : $this->persistEditorHtml($request->option_4, $request);
            $input['option_5'] = empty($request->option_5) || $request->type == 2 ? null : $this->persistEditorHtml($request->option_5, $request);
            $input['answer'] = $request->type == 2 ? null : $request->answer;
            $input['discussion'] = $request->type == 2 ? null : $this->persistEditorHtml($request->discussion, $request);

            if($request->hasFile('audio')){
                $this->deleteFile($question->audio, $this->image_path);
                $file = $request->file('audio')->getClientOriginalName();
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $filename = $this->uploadFile($request->file('audio'), $filename, $this->image_path);
                $input['audio'] = $filename;
            } else {
                $input['audio'] = $question->audio;
            }

            if($request->audio_input == 0 || $request->type == 2) {
                $input['audio'] = null;
                $this->deleteFile($question->audio, $this->image_path);
            }

            return $this->questionRepo->update($input, $id);
        });

        return $permissions;
    }

    private function persistEditorHtml(?string $html, $request): ?string
    {
        return $html === null ? null : $this->editorImageStorage->persistHtml($html, $request->user()->getAuthIdentifier());
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function delete($id)
    {
        $question = $this->find($id);
        $this->deleteFile($question->audio, $this->image_path);
        return $this->questionRepo->delete($id);
    }

    public function getLessonData($id)
    {
        return $this->questionRepo->getLessonData($id);
    }
}
