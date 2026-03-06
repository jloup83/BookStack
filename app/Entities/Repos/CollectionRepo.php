<?php

namespace BookStack\Entities\Repos;

use BookStack\Activity\ActivityType;
use BookStack\Entities\Models\Collection;
use BookStack\Entities\Queries\BookQueries;
use BookStack\Entities\Tools\TrashCan;
use BookStack\Facades\Activity;
use BookStack\Util\DatabaseTransaction;
use Exception;

class CollectionRepo
{
    public function __construct(
        protected BaseRepo $baseRepo,
        protected BookQueries $bookQueries,
        protected TrashCan $trashCan,
    ) {
    }

    /**
     * Create a new collection in the system.
     */
    public function create(array $input, array $bookIds): Collection
    {
        return (new DatabaseTransaction(function () use ($input, $bookIds) {
            $collection = $this->baseRepo->create(new Collection(), $input);
            $this->baseRepo->updateCoverImage($collection, $input['image'] ?? null);
            $this->updateBooks($collection, $bookIds);
            Activity::add(ActivityType::COLLECTION_CREATE, $collection);
            return $collection;
        }))->run();
    }

    /**
     * Update an existing collection in the system using the given input.
     */
    public function update(Collection $collection, array $input, ?array $bookIds): Collection
    {
        $collection = $this->baseRepo->update($collection, $input);

        if (!is_null($bookIds)) {
            $this->updateBooks($collection, $bookIds);
        }

        if (array_key_exists('image', $input)) {
            $this->baseRepo->updateCoverImage($collection, $input['image'], $input['image'] === null);
        }

        Activity::add(ActivityType::COLLECTION_UPDATE, $collection);

        return $collection;
    }

    /**
     * Update which books are assigned to this collection by syncing the given book ids.
     */
    protected function updateBooks(Collection $collection, array $bookIds): void
    {
        $numericIDs = collect($bookIds)->map(function ($id) {
            return intval($id);
        });

        $existingBookIds = $collection->books()->pluck('id')->toArray();
        $visibleExistingBookIds = $this->bookQueries->visibleForList()
            ->whereIn('id', $existingBookIds)
            ->pluck('id')
            ->toArray();
        $nonVisibleExistingBookIds = array_values(array_diff($existingBookIds, $visibleExistingBookIds));

        $newIdsToAssign = $this->bookQueries->visibleForList()
            ->whereIn('id', $bookIds)
            ->pluck('id')
            ->toArray();

        $maxNewIndex = max($numericIDs->keys()->toArray() ?: [0]);

        $syncData = [];
        foreach ($newIdsToAssign as $id) {
            $syncData[$id] = ['order' => $numericIDs->search($id)];
        }

        foreach ($nonVisibleExistingBookIds as $index => $id) {
            $syncData[$id] = ['order' => $maxNewIndex + ($index + 1)];
        }

        $collection->books()->sync($syncData);
    }

    /**
     * Remove a collection from the system.
     *
     * @throws Exception
     */
    public function destroy(Collection $collection): void
    {
        $this->trashCan->softDestroyCollection($collection);
        Activity::add(ActivityType::COLLECTION_DELETE, $collection);
        $this->trashCan->autoClearOld();
    }
}
