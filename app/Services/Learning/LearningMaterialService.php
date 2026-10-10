<?php

namespace App\Services\Learning;

use App\Models\Learning\LearningMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LearningMaterialService
{
    public function paginate(array $filters, int $limit = 12): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->orderBy('category_id')
            ->orderBy('position')
            ->paginate($limit)
            ->withQueryString();
    }

    public function paginateByType(array $filters, string $type, int $limit = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->where('type', $type)
            ->orderBy('position')
            ->orderBy('title')
            ->paginate($limit, ['*'], $pageName)
            ->withQueryString()
            ->appends(['type' => $type]);
    }

    public function create(array $data): LearningMaterial
    {
        return DB::transaction(function () use ($data) {
            $data['exam_id'] = null;
            $data['standalone_since'] = now();

            return LearningMaterial::create($data);
        });
    }

    public function update(LearningMaterial $material, array $data): LearningMaterial
    {
        return DB::transaction(function () use ($material, $data) {
            $material->update($data);

            return $material->refresh();
        });
    }

    public function delete(LearningMaterial $material): void
    {
        DB::transaction(fn () => $material->delete());
    }

    private function filteredQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return LearningMaterial::query()
            ->with(['category', 'exam.category'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"));
                });
            });
    }
}
