<?php

namespace BookStack\Entities\Controllers;

use BookStack\Entities\Queries\BookshelfQueries;
use BookStack\Http\Controller;
use BookStack\Permissions\Permission;

class CollectionController extends Controller
{
    public function __construct(
        protected BookshelfQueries $shelfQueries,
    ) {
    }

    /**
     * Show the form for creating a new collection.
     */
    public function create(?string $shelfSlug = null)
    {
        $this->checkPermission(Permission::BookCreateAll);

        $bookshelf = null;
        if ($shelfSlug !== null) {
            $bookshelf = $this->shelfQueries->findVisibleBySlugOrFail($shelfSlug);
            $this->checkOwnablePermission(Permission::BookshelfUpdate, $bookshelf);
        }

        $this->setPageTitle(trans('entities.collections_create'));

        return view('collections.create', [
            'bookshelf' => $bookshelf,
        ]);
    }
}
