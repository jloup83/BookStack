<?php

namespace BookStack\Entities\Tools;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Bookshelf;
use BookStack\Entities\Models\Collection;
use BookStack\Entities\Queries\BookshelfQueries;
use BookStack\Entities\Queries\CollectionQueries;

class ShelfContext
{
    protected string $KEY_SHELF_CONTEXT_ID = 'context_bookshelf_id';
    protected string $KEY_COLLECTION_CONTEXT_ID = 'context_collection_id';

    public function __construct(
        protected BookshelfQueries $shelfQueries,
        protected CollectionQueries $collectionQueries,
    ) {
    }

    /**
     * Get the current bookshelf context for the given book.
     */
    public function getContextualShelfForBook(Book $book): ?Bookshelf
    {
        $contextBookshelfId = session()->get($this->KEY_SHELF_CONTEXT_ID, null);

        if (!is_int($contextBookshelfId)) {
            return null;
        }

        $shelf = $this->shelfQueries->findVisibleById($contextBookshelfId);
        $shelfContainsBook = $shelf && $shelf->contains($book);

        return $shelfContainsBook ? $shelf : null;
    }

    /**
     * Get the current collection context for the given book.
     */
    public function getContextualCollectionForBook(Book $book): ?Collection
    {
        $contextCollectionId = session()->get($this->KEY_COLLECTION_CONTEXT_ID, null);

        if (!is_int($contextCollectionId)) {
            return null;
        }

        $collection = $this->collectionQueries->findVisibleById($contextCollectionId);
        $collectionContainsBook = $collection && $collection->contains($book);

        return $collectionContainsBook ? $collection : null;
    }

    /**
     * Get the current bookshelf context for the given collection.
     */
    public function getContextualShelfForCollection(Collection $collection): ?Bookshelf
    {
        $contextBookshelfId = session()->get($this->KEY_SHELF_CONTEXT_ID, null);

        if (!is_int($contextBookshelfId)) {
            return null;
        }

        $shelf = $this->shelfQueries->findVisibleById($contextBookshelfId);
        if (!$shelf) {
            return null;
        }

        $shelfContainsCollection = $shelf->collections()
            ->where('entities.id', '=', $collection->id)->exists();

        return $shelfContainsCollection ? $shelf : null;
    }

    /**
     * Store the current contextual shelf ID.
     */
    public function setShelfContext(int $shelfId): void
    {
        session()->put($this->KEY_SHELF_CONTEXT_ID, $shelfId);
    }

    /**
     * Store the current contextual collection ID.
     */
    public function setCollectionContext(int $collectionId): void
    {
        session()->put($this->KEY_COLLECTION_CONTEXT_ID, $collectionId);
    }

    /**
     * Clear the session stored shelf context id.
     */
    public function clearShelfContext(): void
    {
        session()->forget($this->KEY_SHELF_CONTEXT_ID);
    }

    /**
     * Clear the session stored collection context id.
     */
    public function clearCollectionContext(): void
    {
        session()->forget($this->KEY_COLLECTION_CONTEXT_ID);
    }
}
