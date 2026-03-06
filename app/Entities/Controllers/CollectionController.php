<?php

namespace BookStack\Entities\Controllers;

use BookStack\Activity\ActivityQueries;
use BookStack\Activity\Models\View;
use BookStack\Entities\Queries\BookQueries;
use BookStack\Entities\Queries\BookshelfQueries;
use BookStack\Entities\Queries\CollectionQueries;
use BookStack\Entities\Queries\EntityQueries;
use BookStack\Entities\Repos\CollectionRepo;
use BookStack\Exceptions\ImageUploadException;
use BookStack\Exceptions\NotFoundException;
use BookStack\Http\Controller;
use BookStack\Permissions\Permission;
use BookStack\References\ReferenceFetcher;
use BookStack\Util\SimpleListOptions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CollectionController extends Controller
{
    public function __construct(
        protected CollectionRepo $collectionRepo,
        protected CollectionQueries $queries,
        protected EntityQueries $entityQueries,
        protected BookQueries $bookQueries,
        protected BookshelfQueries $shelfQueries,
        protected ReferenceFetcher $referenceFetcher,
    ) {
    }

    /**
     * Display a listing of collections.
     */
    public function index(Request $request)
    {
        $view = setting()->getForCurrentUser('collections_view_type');
        $listOptions = SimpleListOptions::fromRequest($request, 'collections')->withSortOptions([
            'name'       => trans('common.sort_name'),
            'created_at' => trans('common.sort_created_at'),
            'updated_at' => trans('common.sort_updated_at'),
        ]);

        $collections = $this->queries->visibleForListWithCover()
            ->orderBy($listOptions->getSort(), $listOptions->getOrder())
            ->paginate(18);

        $this->setPageTitle(trans('entities.collections'));

        return view('collections.index', [
            'collections' => $collections,
            'view'        => $view,
            'listOptions' => $listOptions,
        ]);
    }

    /**
     * Show the form for creating a new collection.
     */
    public function create(?string $shelfSlug = null)
    {
        $this->checkPermission(Permission::CollectionCreateAll);

        $bookshelf = null;
        if ($shelfSlug !== null) {
            $bookshelf = $this->shelfQueries->findVisibleBySlugOrFail($shelfSlug);
            $this->checkOwnablePermission(Permission::BookshelfUpdate, $bookshelf);
        }

        $books = $this->bookQueries->visibleForList()->orderBy('name')->get(['name', 'id', 'slug', 'created_at', 'updated_at']);
        $this->setPageTitle(trans('entities.collections_create'));

        return view('collections.create', [
            'bookshelf' => $bookshelf,
            'books'     => $books,
        ]);
    }

    /**
     * Store a newly created collection in storage.
     *
     * @throws ValidationException
     * @throws ImageUploadException
     */
    public function store(Request $request, ?string $shelfSlug = null)
    {
        $this->checkPermission(Permission::CollectionCreateAll);
        $validated = $this->validate($request, [
            'name'             => ['required', 'string', 'max:255'],
            'description_html' => ['string', 'max:2000'],
            'image'            => array_merge(['nullable'], $this->getImageValidationRules()),
            'tags'             => ['array'],
        ]);

        $bookIds = explode(',', $request->get('books', ''));
        $collection = $this->collectionRepo->create($validated, $bookIds);

        $bookshelf = null;
        if ($shelfSlug !== null) {
            $bookshelf = $this->shelfQueries->findVisibleBySlugOrFail($shelfSlug);
            $this->checkOwnablePermission(Permission::BookshelfUpdate, $bookshelf);
            $bookshelf->collections()->attach($collection->id, ['order' => $bookshelf->collections()->count()]);
        }

        return redirect($collection->getUrl());
    }

    /**
     * Display the specified collection.
     *
     * @throws NotFoundException
     */
    public function show(Request $request, ActivityQueries $activities, string $slug)
    {
        try {
            $collection = $this->queries->findVisibleBySlugOrFail($slug);
        } catch (NotFoundException $exception) {
            $collection = $this->entityQueries->findVisibleByOldSlugs('collection', $slug);
            if (is_null($collection)) {
                throw $exception;
            }
            return redirect($collection->getUrl());
        }

        $this->checkOwnablePermission(Permission::CollectionView, $collection);

        $listOptions = SimpleListOptions::fromRequest($request, 'collection_books')->withSortOptions([
            'default'    => trans('common.sort_default'),
            'name'       => trans('common.sort_name'),
            'created_at' => trans('common.sort_created_at'),
            'updated_at' => trans('common.sort_updated_at'),
        ]);

        $sort = $listOptions->getSort();
        $sortedVisibleBooks = $collection->visibleBooks()
            ->reorder($sort === 'default' ? 'order' : $sort, $listOptions->getOrder())
            ->get()
            ->values()
            ->all();

        View::incrementFor($collection);
        $view = setting()->getForCurrentUser('collection_view_type');

        $this->setPageTitle($collection->getShortName());

        return view('collections.show', [
            'collection'         => $collection,
            'sortedVisibleBooks' => $sortedVisibleBooks,
            'view'               => $view,
            'activity'           => $activities->entityActivity($collection, 20, 1),
            'listOptions'        => $listOptions,
            'referenceCount'     => $this->referenceFetcher->getReferenceCountToEntity($collection),
        ]);
    }

    /**
     * Show the form for editing the specified collection.
     */
    public function edit(string $slug)
    {
        $collection = $this->queries->findVisibleBySlugOrFail($slug);
        $this->checkOwnablePermission(Permission::CollectionUpdate, $collection);

        $collectionBookIds = $collection->books()->get(['id'])->pluck('id');
        $books = $this->bookQueries->visibleForList()
            ->whereNotIn('id', $collectionBookIds)
            ->orderBy('name')
            ->get(['name', 'id', 'slug', 'created_at', 'updated_at']);

        $this->setPageTitle(trans('entities.collections_edit_named', ['name' => $collection->getShortName()]));

        return view('collections.edit', [
            'collection' => $collection,
            'books'      => $books,
        ]);
    }

    /**
     * Update the specified collection in storage.
     *
     * @throws ValidationException
     * @throws ImageUploadException
     * @throws NotFoundException
     */
    public function update(Request $request, string $slug)
    {
        $collection = $this->queries->findVisibleBySlugOrFail($slug);
        $this->checkOwnablePermission(Permission::CollectionUpdate, $collection);
        $validated = $this->validate($request, [
            'name'             => ['required', 'string', 'max:255'],
            'description_html' => ['string', 'max:2000'],
            'image'            => array_merge(['nullable'], $this->getImageValidationRules()),
            'tags'             => ['array'],
        ]);

        if ($request->has('image_reset')) {
            $validated['image'] = null;
        } elseif (array_key_exists('image', $validated) && is_null($validated['image'])) {
            unset($validated['image']);
        }

        $bookIds = explode(',', $request->get('books', ''));
        $collection = $this->collectionRepo->update($collection, $validated, $bookIds);

        return redirect($collection->getUrl());
    }

    /**
     * Shows the page to confirm deletion.
     */
    public function showDelete(string $slug)
    {
        $collection = $this->queries->findVisibleBySlugOrFail($slug);
        $this->checkOwnablePermission(Permission::CollectionDelete, $collection);

        $this->setPageTitle(trans('entities.collections_delete_named', ['name' => $collection->getShortName()]));

        return view('collections.delete', ['collection' => $collection]);
    }

    /**
     * Remove the specified collection from storage.
     *
     * @throws Exception
     */
    public function destroy(string $slug)
    {
        $collection = $this->queries->findVisibleBySlugOrFail($slug);
        $this->checkOwnablePermission(Permission::CollectionDelete, $collection);

        $this->collectionRepo->destroy($collection);

        return redirect('/collections');
    }
}
