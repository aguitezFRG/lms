<?php

namespace App\Filament\Resources\User\Catalogs\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\User\Catalogs\CatalogResource;
use App\Models\RrMaterialParents;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ListCatalogs extends Page
{
    protected static string $resource = CatalogResource::class;

    protected string $view = 'filament.resources.user.list-catalog';

    protected static ?string $title = 'Material Catalog';

    protected ?string $pollingInterval = '120s';

    // ── Applied filter state (what the query actually uses) ─────────────────
    public string $search = '';

    public string $searchScope = 'all';

    public string $typeFilter = '';

    public string $formatFilter = '';

    public string $pubDateFrom = '';

    public string $pubDateTo = '';

    public array $sdgFilter = [];

    public string $sortBy = 'publication_date';

    public string $sortDir = 'desc';

    public bool $availableOnly = true;  // hide unavailable by default

    public int $page = 1;

    public int $perPage = 15;

    public string $draftTypeFilter = '';

    public string $draftFormatFilter = '';

    public string $draftPubDateFrom = '';

    public string $draftPubDateTo = '';

    public array $draftSdgFilter = [];

    public bool $draftAvailableOnly = true;

    // ── Skeleton loading state ───────────────────────────────────────────────
    public bool $isLoading = false;

    // ── Filter panel open/closed ─────────────────────────────────────────────
    public bool $filterPanelOpen = false;

    // ── Header Actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'searchScope' => ['except' => 'all'],
        'typeFilter' => ['except' => ''],
        'formatFilter' => ['except' => ''],
        'pubDateFrom' => ['except' => ''],
        'pubDateTo' => ['except' => ''],
        'sdgFilter' => ['except' => []],
        'sortBy' => ['except' => 'publication_date'],
        'sortDir' => ['except' => 'desc'],
        'availableOnly' => ['except' => true],
        'page' => ['except' => 1],
    ];

    // ── Sync draft from applied on page load (covers URL query params) ───────

    public function mount(): void
    {
        $this->draftTypeFilter = $this->typeFilter;
        $this->draftFormatFilter = $this->formatFilter;
        $this->draftPubDateFrom = $this->pubDateFrom;
        $this->draftPubDateTo = $this->pubDateTo;
        $this->draftSdgFilter = $this->sdgFilter;
        $this->draftAvailableOnly = $this->availableOnly;
    }

    // ── Lifecycle hooks — only search/sort apply live ────────────────────────

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedSearchScope(): void
    {
        $this->page = 1;
    }

    public function updatedSortBy(): void
    {
        $this->page = 1;
    }

    public function updatedSortDir(): void
    {
        $this->page = 1;
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function goToPage(int $p): void
    {
        $this->page = $p;
    }

    // ── Apply staged → applied ───────────────────────────────────────────────

    public function applyFilters(): void
    {
        $this->typeFilter = $this->draftTypeFilter;
        $this->formatFilter = $this->draftFormatFilter;
        $this->pubDateFrom = $this->draftPubDateFrom;
        $this->pubDateTo = $this->draftPubDateTo;
        $this->sdgFilter = $this->draftSdgFilter;
        $this->availableOnly = $this->draftAvailableOnly;
        $this->page = 1;
        $this->filterPanelOpen = false;
    }

    // ── Draft filter helpers ────────────────────────────────────────────

    public function toggleDraftSdg(string $sdg): void
    {
        if (in_array($sdg, $this->draftSdgFilter)) {
            $this->draftSdgFilter = array_values(
                array_filter($this->draftSdgFilter, fn ($s) => $s !== $sdg)
            );
        } else {
            $this->draftSdgFilter[] = $sdg;
        }
    }

    public function clearDraftFilters(): void
    {
        $this->draftTypeFilter = '';
        $this->draftFormatFilter = '';
        $this->draftPubDateFrom = '';
        $this->draftPubDateTo = '';
        $this->draftSdgFilter = [];
        $this->draftAvailableOnly = true;
    }

    // ── Clear all applied filters ─────────────────────────────────────────────

    public function clearAllFilters(): void
    {
        $this->typeFilter = '';
        $this->formatFilter = '';
        $this->pubDateFrom = '';
        $this->pubDateTo = '';
        $this->sdgFilter = [];
        $this->availableOnly = true;

        // Sync draft to match applied
        $this->clearDraftFilters();

        $this->page = 1;
    }

    // ── Remove a single chip ─────────────────────────────────────────────────

    public function removeFilter(string $filter, ?string $value = null): void
    {
        match ($filter) {
            'typeFilter' => $this->typeFilter = '',
            'formatFilter' => $this->formatFilter = '',
            'pubDate' => [$this->pubDateFrom = '', $this->pubDateTo = ''],
            'availableOnly' => $this->availableOnly = true,  // revert to default (hide unavailable)
            'sdg' => $this->sdgFilter = array_values(
                array_filter($this->sdgFilter, fn ($s) => $s !== $value)
            ),
            default => null,
        };

        // Keep draft in sync
        $this->draftTypeFilter = $this->typeFilter;
        $this->draftFormatFilter = $this->formatFilter;
        $this->draftPubDateFrom = $this->pubDateFrom;
        $this->draftPubDateTo = $this->pubDateTo;
        $this->draftSdgFilter = $this->sdgFilter;
        $this->draftAvailableOnly = $this->availableOnly;

        $this->page = 1;
    }

    // ── Filter panel toggle ───────────────────────────────────────────────────

    public function toggleFilterPanel(): void
    {
        $this->filterPanelOpen = ! $this->filterPanelOpen;
    }

    // ── Sort direction toggle ─────────────────────────────────────────────────

    public function toggleSortDir(): void
    {
        $this->sortDir = $this->sortDir === 'desc' ? 'asc' : 'desc';
        $this->page = 1;
    }

    // ── Optimised query ───────────────────────────────────────────────────────

    protected function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $userLevel = UserRole::from($user->role)->getAccessLevel();

        return RrMaterialParents::query()
            // Only the columns needed for the card list — keeps the result set lean
            ->select([
                'id', 'title', 'author', 'material_type',
                'access_level', 'publication_date',
                'keywords', 'abstract', 'created_at',
            ])
            ->where('access_level', '<=', $userLevel)

            // ── Search ───────────────────────────────────────────────────────
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    match ($this->searchScope) {
                        'title' => $inner->where('title', 'like', $term),
                        'author' => $inner->where('author', 'like', $term),
                        'keyword' => $inner->where('keywords', 'like', $term),
                        default => $inner
                            ->where('title', 'like', $term)
                            ->orWhere('author', 'like', $term)
                            ->orWhere('keywords', 'like', $term),
                    };
                });
            })

            // ── Type ─────────────────────────────────────────────────────────
            ->when($this->typeFilter !== '', fn ($q) => $q->where('material_type', $this->typeFilter)
            )

            // ── Format ───────────────────────────────────────────────────────
            ->when($this->formatFilter === 'digital', fn ($q) => $q->whereHas('materials', fn ($m) => $m->where('is_digital', true)
                ->where('is_available', true)
                ->whereNull('deleted_at')
            )
            )
            ->when($this->formatFilter === 'physical', fn ($q) => $q->whereHas('materials', fn ($m) => $m->where('is_digital', false)
                ->where('is_available', true)
                ->whereNull('deleted_at')
            )
            )

            // ── Publication date range ────────────────────────────────────────
            ->when($this->pubDateFrom !== '', fn ($q) => $q->whereDate('publication_date', '>=', $this->pubDateFrom)
            )
            ->when($this->pubDateTo !== '', fn ($q) => $q->whereDate('publication_date', '<=', $this->pubDateTo)
            )

            // ── SDG multi-select (OR) ─────────────────────────────────────────
            ->when(! empty($this->sdgFilter), function ($q) {
                $q->where(function ($inner) {
                    foreach ($this->sdgFilter as $sdg) {
                        $driver = config('database.default');
                        if ($driver === 'mysql') {
                            $inner->orWhereRaw(
                                'JSON_CONTAINS(sdgs, ?)',
                                [json_encode($sdg)]
                            );
                        } else {
                            $inner->orWhere('sdgs', 'like', '%"'.addslashes($sdg).'"%');
                        }
                    }
                });
            })

            // ── Availability guard ────────────────────────────────────────────
            ->when($this->availableOnly, fn ($q) => $q->whereHas('materials', fn ($m) => $m->where('is_available', true)->whereNull('deleted_at')
            )
            )

            // ── Sort ──────────────────────────────────────────────────────────
            ->orderBy($this->sortBy, $this->sortDir);
    }

    // ── View data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        // Limit to 50 per page max; default is 15
        $clampedPerPage = min($this->perPage, 50);

        $paginator = $this->getQuery()
            ->with([
                // Only load what the card needs — avoid N+1 on the materials sub-query
                'materials' => fn ($q) => $q
                    ->select(['id', 'material_parent_id', 'is_digital', 'is_available', 'deleted_at'])
                    ->whereNull('deleted_at'),
            ])
            ->paginate($clampedPerPage, ['*'], 'page', $this->page);

        $materials = collect($paginator->items())->map(fn (RrMaterialParents $m) => [
            'id' => $m->id,
            'title' => $m->title,
            'author' => $m->author,
            'material_type' => $m->material_type,
            'access_level' => $m->access_level,
            'publication_date' => $m->publication_date?->format('Y'),
            'keywords' => is_array($m->keywords) ? $m->keywords : [],
            'abstract' => $m->abstract,
            // Derive availability from the already-eager-loaded relation
            'has_digital' => $m->materials->contains(
                fn ($c) => $c->is_digital && $c->is_available
            ),
            'has_physical' => $m->materials->contains(
                fn ($c) => ! $c->is_digital && $c->is_available
            ),
            'view_url' => CatalogResource::getUrl('view', ['record' => $m->id]),
        ])->toArray();

        // Active filter count (for badge display on the "Filters" button)
        $activeFilterCount = collect([
            $this->typeFilter !== '',
            $this->formatFilter !== '',
            $this->pubDateFrom !== '',
            $this->pubDateTo !== '',
            ...array_fill(0, count($this->sdgFilter), true),
            $this->availableOnly === false,  // only count if it's hiding unavailable items (non-default)
        ])->filter()->count();

        // Draft badge count (inside the modal)
        $draftFilterCount = collect([
            $this->draftTypeFilter !== '',
            $this->draftFormatFilter !== '',
            $this->draftPubDateFrom !== '',
            $this->draftPubDateTo !== '',
            ...array_fill(0, count($this->draftSdgFilter), true),
            $this->draftAvailableOnly === false,  // only count if it's hiding unavailable items (non-default)
        ])->filter()->count();

        return [
            'materials' => $materials,
            'paginator' => $paginator,
            'totalResults' => $paginator->total(),
            'activeFilterCount' => $activeFilterCount,
            'draftFilterCount' => $draftFilterCount,
        ];
    }
}
