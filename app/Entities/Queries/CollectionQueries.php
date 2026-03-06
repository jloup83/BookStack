<?php

namespace BookStack\Entities\Queries;

use BookStack\Entities\Models\Collection;
use BookStack\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements ProvidesEntityQueries<Collection>
 */
class CollectionQueries implements ProvidesEntityQueries
{
    protected static array $listAttributes = [
        'id', 'slug', 'name', 'description',
        'created_at', 'updated_at', 'image_id', 'owned_by',
    ];

    /**
     * @return Builder<Collection>
     */
    public function start(): Builder
    {
        return Collection::query();
    }

    public function findVisibleById(int $id): ?Collection
    {
        return $this->start()->scopes('visible')->find($id);
    }

    public function findVisibleByIdOrFail(int $id): Collection
    {
        $collection = $this->findVisibleById($id);

        if (is_null($collection)) {
            throw new NotFoundException(trans('errors.collection_not_found'));
        }

        return $collection;
    }

    public function findVisibleBySlugOrFail(string $slug): Collection
    {
        /** @var ?Collection $collection */
        $collection = $this->start()
            ->scopes('visible')
            ->where('slug', '=', $slug)
            ->first();

        if ($collection === null) {
            throw new NotFoundException(trans('errors.collection_not_found'));
        }

        return $collection;
    }

    public function visibleForList(): Builder
    {
        return $this->start()->scopes('visible')->select(static::$listAttributes);
    }

    public function visibleForContent(): Builder
    {
        return $this->start()->scopes('visible');
    }

    public function visibleForListWithCover(): Builder
    {
        return $this->visibleForList()->with('cover');
    }

    public function recentlyViewedForCurrentUser(): Builder
    {
        return $this->visibleForList()
            ->scopes('withLastView')
            ->having('last_viewed_at', '>', 0)
            ->orderBy('last_viewed_at', 'desc');
    }

    public function popularForList(): Builder
    {
        return $this->visibleForList()
            ->scopes('withViewCount')
            ->having('view_count', '>', 0)
            ->orderBy('view_count', 'desc');
    }
}
