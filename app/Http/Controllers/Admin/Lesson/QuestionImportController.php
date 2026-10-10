<?php

namespace App\Http\Controllers\Admin\Lesson;

use App\Exceptions\QuestionImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\QuestionImportCommitRequest;
use App\Http\Requests\Lesson\QuestionImportPreviewRequest;
use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use App\Services\Lesson\QuestionDocumentImportService;
use App\Services\Lesson\QuestionDocumentTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionImportController extends Controller
{
    public function __construct(
        private readonly QuestionDocumentImportService $importService,
        private readonly QuestionDocumentTemplateService $templateService
    ) {
    }

    public function create(string $questionTitleId): Response
    {
        return inertia('Admin/Lesson/Question/Import', $this->pageData($this->questionTitle($questionTitleId)));
    }

    public function template(string $questionTitleId): StreamedResponse
    {
        $questionTitle = $this->questionTitle($questionTitleId);
        $binary = $this->templateService->generateDocx($questionTitle);
        $slug = Str::slug($questionTitle->category?->name ?? 'soal');
        $filename = "format-soal-{$slug}.docx";

        return response()->streamDownload(
            fn () => print($binary),
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Cache-Control' => 'no-cache, private',
            ]
        );
    }

    public function preview(string $questionTitleId, QuestionImportPreviewRequest $request): Response|RedirectResponse
    {
        $questionTitle = $this->questionTitle($questionTitleId);

        try {
            $preview = $this->importService->preview(
                $request->file('document'),
                $questionTitle,
                (string) $request->user()->id,
            );
        } catch (QuestionImportException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return inertia('Admin/Lesson/Question/Import', [
            ...$this->pageData($questionTitle),
            'preview' => $preview,
        ]);
    }

    public function store(string $questionTitleId, QuestionImportCommitRequest $request): RedirectResponse
    {
        $questionTitle = $this->questionTitle($questionTitleId);

        try {
            $count = $this->importService->commit(
                $request->string('token')->toString(),
                $questionTitle,
                (string) $request->user()->id,
            );
        } catch (QuestionImportException $exception) {
            return back()->withErrors(['import' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.question-titles.questions.index', $questionTitle->id)
            ->with('success', "{$count} soal berhasil diimpor.");
    }

    private function questionTitle(string $id): QuestionTitle
    {
        return QuestionTitle::query()->with('category')->findOrFail($id);
    }

    private function pageData(QuestionTitle $questionTitle): array
    {
        $valueCategories = ValueCategory::query()
            ->where('category_id', $questionTitle->category_id)
            ->orderBy('section')
            ->get(['id', 'name', 'section', 'assessment_type']);

        return [
            'questionTitle' => $questionTitle,
            'valueCategories' => $valueCategories,
            'preview' => null,
            'templateDownloadUrl' => route('admin.question-titles.questions.import.template', $questionTitle->id),
            'sampleFormatText' => $this->templateService->getTemplateContent($questionTitle),
            'hasOptionPoints' => $valueCategories->contains(fn ($c) => (int) $c->assessment_type === ValueCategory::ASSESSMENT_OPTION_POINTS),
        ];
    }
}
