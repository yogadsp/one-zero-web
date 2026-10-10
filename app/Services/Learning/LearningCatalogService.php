<?php

namespace App\Services\Learning;

use App\Models\Exam\Exam;
use App\Models\Learning\LearningMaterial;
use App\Models\MasterData\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LearningCatalogService
{
    public const TYPE_TRYOUT = 'tryout';

    public const TYPES = [
        self::TYPE_TRYOUT,
        LearningMaterial::TYPE_VIDEO,
        LearningMaterial::TYPE_LIVE_CLASS,
    ];

    public function __construct(private readonly LearningAccessService $accessService)
    {
    }

    public function catalogFor(User $user, array $filters, int $limit = 8): LengthAwarePaginator
    {
        $type = $this->requestedType($filters);
        $query = $type === self::TYPE_TRYOUT
            ? $this->tryoutQuery($user)
            : $this->materialQuery($type)->whereNotNull('price');

        $this->applyFilters($query, $filters);
        $paginator = $query->orderByDesc('created_at')->paginate($limit)->withQueryString();
        $this->decorate($paginator->getCollection(), $user);

        return $paginator;
    }

    public function recommendationsFor(User $user, int $limit = 4): Collection
    {
        $items = collect();

        foreach (self::TYPES as $type) {
            $query = $type === self::TYPE_TRYOUT
                ? $this->tryoutQuery($user)
                : $this->materialQuery($type)->whereNotNull('price');
            $items = $items->concat($query->latest()->limit(2)->get());
        }

        return $this->decorate(
            $items->sortByDesc(fn (Model $item) => $item->getRawOriginal('created_at'))->take($limit)->values(),
            $user,
        );
    }

    public function ownedPackagesFor(User $user, array $filters, int $limit = 8): LengthAwarePaginator
    {
        $type = $this->requestedType($filters);

        if ($type === self::TYPE_TRYOUT) {
            $query = $this->tryoutQuery($user)
                ->whereHas('transactions', fn (Builder $transactions) => $this->applyEntitlement($transactions, $user));
        } else {
            $query = $this->materialQuery($type)->where(function (Builder $materials) use ($user) {
                $materials
                    ->whereHas('transactions', fn (Builder $transactions) => $this->applyEntitlement($transactions, $user))
                    ->orWhere(function (Builder $legacy) use ($user) {
                        $legacy->whereNotNull('exam_id')
                            ->whereHas('exam.transactions', function (Builder $transactions) use ($user) {
                                $this->applyEntitlement($transactions, $user)
                                    ->whereColumn('transactions.created_at', '<=', 'learning_materials.standalone_since');
                            });
                    });
            });
        }

        $this->applyFilters($query, $filters);
        $paginator = $query->orderByDesc('created_at')->paginate($limit)->withQueryString();
        $this->decorate($paginator->getCollection(), $user);

        return $paginator;
    }

    public function ownedPackageCountFor(User $user): int
    {
        return collect(self::TYPES)->sum(function (string $type) use ($user) {
            return $this->ownedPackagesFor($user, ['type' => $type], 1)->total();
        });
    }

    public function itemFor(User $user, string $type, string $id): ?Model
    {
        $item = $type === self::TYPE_TRYOUT
            ? $this->tryoutQuery($user)->find($id)
            : $this->materialQuery($type)->find($id);

        if (! $item) {
            return null;
        }

        if ($item instanceof LearningMaterial
            && $item->price === null
            && ! $this->accessService->canAccessMaterial($user, $item)) {
            return null;
        }

        $this->decorate(collect([$item]), $user);

        return $item;
    }

    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    private function tryoutQuery(User $user): Builder
    {
        return Exam::query()
            ->with('category')
            ->withExists(['transactions as purchased_access' => fn (Builder $query) => $this->applyEntitlement($query, $user)]);
    }

    private function materialQuery(string $type): Builder
    {
        return LearningMaterial::query()
            ->with(['category', 'exam'])
            ->where('type', $type)
            ->where('is_published', true);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = (string) ($filters['category'] ?? '');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"));
            });
        }

        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }
    }

    private function decorate(Collection $items, User $user): Collection
    {
        return $items->each(function (Model $item) use ($user) {
            if ($item instanceof Exam) {
                $item->setAttribute('product_type', self::TYPE_TRYOUT);
                $item->setAttribute('has_access', (bool) $item->purchased_access);

                return;
            }

            $item->setAttribute('product_type', $item->type);
            $item->setAttribute('has_access', $this->accessService->canAccessMaterial($user, $item));
            $item->makeHidden(['url']);
        });
    }

    private function applyEntitlement(Builder $query, User $user): Builder
    {
        return $query
            ->where('user_id', $user->id)
            ->where('transaction_status', 'done')
            ->where('voucher_activated', true);
    }

    private function requestedType(array $filters): string
    {
        $type = (string) ($filters['type'] ?? self::TYPE_TRYOUT);

        return in_array($type, self::TYPES, true) ? $type : self::TYPE_TRYOUT;
    }
}
