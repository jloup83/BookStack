<?php

namespace BookStack\Entities;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Collection;
use BookStack\Entities\Tools\ShelfContext;
use Illuminate\View\View;

class BreadcrumbsViewComposer
{
    public function __construct(
        protected ShelfContext $shelfContext
    ) {
    }

    /**
     * Modify data when the view is composed.
     */
    public function compose(View $view): void
    {
        $crumbs = $view->getData()['crumbs'];
        $firstCrumb = $crumbs[0] ?? null;

        if ($firstCrumb instanceof Book) {
            // Check for collection context first
            $collection = $this->shelfContext->getContextualCollectionForBook($firstCrumb);
            if ($collection) {
                array_unshift($crumbs, $collection);
                // Also check if this collection has a shelf context
                $shelf = $this->shelfContext->getContextualShelfForCollection($collection);
                if ($shelf) {
                    array_unshift($crumbs, $shelf);
                }
            } else {
                // Fall back to direct shelf context
                $shelf = $this->shelfContext->getContextualShelfForBook($firstCrumb);
                if ($shelf) {
                    array_unshift($crumbs, $shelf);
                }
            }
            $view->with('crumbs', $crumbs);
        }

        if ($firstCrumb instanceof Collection) {
            $shelf = $this->shelfContext->getContextualShelfForCollection($firstCrumb);
            if ($shelf) {
                array_unshift($crumbs, $shelf);
                $view->with('crumbs', $crumbs);
            }
        }
    }
}
