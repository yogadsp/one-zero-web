<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\LearningMaterialIndexRequest;
use App\Http\Requests\Learning\LearningMaterialRequest;
use App\Models\Learning\LearningMaterial;
use App\Models\MasterData\Category;
use App\Services\Learning\LearningMaterialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningMaterialController extends Controller
{
    public function __construct(private readonly LearningMaterialService $materialService)
    {
    }

    public function index(LearningMaterialIndexRequest $request)
    {
        return inertia('Admin/Learning/Material/Index', [
            'liveClasses' => $this->materialService->paginateByType(
                $request->validated(),
                LearningMaterial::TYPE_LIVE_CLASS,
                pageName: 'class_page',
            ),
            'videos' => $this->materialService->paginateByType(
                $request->validated(),
                LearningMaterial::TYPE_VIDEO,
                pageName: 'video_page',
            ),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->validated(),
            'activeType' => $request->validated('type', LearningMaterial::TYPE_LIVE_CLASS),
        ]);
    }

    public function store(LearningMaterialRequest $request)
    {
        $this->materialService->create($request->validated());

        return $this->redirectToIndex($request, 'Materi belajar berhasil ditambahkan.');
    }

    public function update(LearningMaterialRequest $request, LearningMaterial $learningMaterial)
    {
        $this->materialService->update($learningMaterial, $request->validated());

        return $this->redirectToIndex($request, 'Materi belajar berhasil diperbarui.');
    }

    public function destroy(Request $request, LearningMaterial $learningMaterial)
    {
        $this->materialService->delete($learningMaterial);

        return $this->redirectToIndex($request, 'Materi belajar berhasil dihapus.');
    }

    private function redirectToIndex(Request $request, string $message)
    {
        $request->session()->flash('success', $message);
        $location = route('admin.learning-materials.index');

        return $request->header('X-Inertia')
            ? Inertia::location($location)
            : redirect($location);
    }
}
