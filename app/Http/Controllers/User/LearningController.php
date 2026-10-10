<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\LearningCatalogRequest;
use App\Models\Learning\LearningMaterial;
use App\Services\Learning\LearningAccessService;
use App\Services\Learning\LearningCatalogService;
use Illuminate\Http\RedirectResponse;

class LearningController extends Controller
{
    public function __construct(
        private readonly LearningCatalogService $catalogService,
        private readonly LearningAccessService $accessService,
    ) {
    }

    public function index(LearningCatalogRequest $request)
    {
        $filters = $request->validated();

        return inertia('User/Learning/Index', [
            'packages' => $this->catalogService->catalogFor($request->user(), $filters),
            'categories' => $this->catalogService->categories(),
            'filters' => $filters,
        ]);
    }

    public function myPackages(LearningCatalogRequest $request)
    {
        $filters = $request->validated();

        return inertia('User/Learning/MyPackages', [
            'packages' => $this->catalogService->ownedPackagesFor($request->user(), $filters),
            'categories' => $this->catalogService->categories(),
            'filters' => $filters,
        ]);
    }

    public function show(LearningCatalogRequest $request, string $type, string $id)
    {
        abort_unless(in_array($type, LearningCatalogService::TYPES, true), 404);
        $product = $this->catalogService->itemFor($request->user(), $type, $id);

        abort_unless($product, 404);

        return inertia('User/Learning/Show', [
            'product' => $product,
        ]);
    }

    public function showLegacyPackage(LearningCatalogRequest $request, string $id)
    {
        return redirect()->route('user.learning.show', [LearningCatalogService::TYPE_TRYOUT, $id]);
    }

    public function openMaterial(LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->authorizeMaterialAccess($learningMaterial);

        return redirect()->away($learningMaterial->url);
    }

    public function embedMaterial(LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->authorizeMaterialAccess($learningMaterial);

        $url = $learningMaterial->url;
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $videoId = null;

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $videoId = $query['v'] ?? null;
        } elseif ($host === 'youtu.be') {
            $videoId = trim($parts['path'] ?? '', '/');
        }

        if ($videoId && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return redirect()->away('https://www.youtube.com/embed/'.$videoId);
        }

        return redirect()->away($url);
    }

    private function authorizeMaterialAccess(LearningMaterial $learningMaterial): void
    {
        abort_unless($learningMaterial->is_published, 404);
        abort_unless($this->accessService->canAccessMaterial(request()->user(), $learningMaterial), 403);
    }
}
