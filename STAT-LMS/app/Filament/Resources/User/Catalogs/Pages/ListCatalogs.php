<?php

namespace App\Filament\Resources\User\Catalogs\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\User\Catalogs\CatalogResource;
use App\Models\RrMaterialParents;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ListCatalogs extends Page
{
    protected static string $resource = CatalogResource::class;

    protected string $view   = 'filament.resources.user.list-catalog';
    protected static ?string $title = 'Material Catalog';

    // ── Livewire state ──────────────────────────────────────────────────────
    public string $search       = '';
    public string $typeFilter   = '';
    public string $formatFilter = '';
    public int    $page         = 1;
    public int    $perPage      = 15;

    /* ── OPAC Extended Filter State ─────────────────────────────────── */
    public string $searchScope   = 'all';        // all | title | author | keyword
    public string $pubDateFrom   = '';           // YYYY-MM-DD lower bound on publication_date
    public string $pubDateTo     = '';           // YYYY-MM-DD upper bound on publication_date
    public array  $sdgFilter     = [];
    public string $sortBy        = 'created_at';
    public string $sortDir       = 'desc';  // asc | desc
    public bool   $availableOnly = false;   // only show items with available copies
    /* ──────────────────────────────────────────────────────────────────── */

    protected $queryString = [
        'search'        => ['except' => ''],
        'searchScope'   => ['except' => 'all'],
        'typeFilter'    => ['except' => ''],
        'formatFilter'  => ['except' => ''],
        'pubDateFrom'   => ['except' => ''],
        'pubDateTo'     => ['except' => ''],
        'sdgFilter'     => ['except' => []],
        'sortBy'        => ['except' => 'created_at'],
        'sortDir'       => ['except' => 'desc'],
        'availableOnly' => ['except' => false],
        'page'          => ['except' => 1],
    ];

    // ── Livewire lifecycle ──────────────────────────────────────────────────
    public function updatedSearch(): void       { $this->page = 1; }
    public function updatedTypeFilter(): void   { $this->page = 1; }
    public function updatedFormatFilter(): void { $this->page = 1; }

    /* OPAC lifecycle resets */
    public function updatedSearchScope(): void    { $this->page = 1; }
    public function updatedPubDateFrom(): void    { $this->page = 1; }
    public function updatedPubDateTo(): void      { $this->page = 1; }
    public function updatedSortBy(): void         { $this->page = 1; }
    public function updatedAvailableOnly(): void  { $this->page = 1; }
    /* ──────────────────────────────────────────────────────────────────── */

    public function nextPage(): void       { $this->page++; }
    public function previousPage(): void   { if ($this->page > 1) $this->page--; }
    public function goToPage(int $p): void { $this->page = $p; }

    /* ── OPAC Filter Actions ──────────────────────────────────────── */

    /** Toggle a single SDG on/off in the multi-select filter array */
    public function toggleSdg(string $sdg): void
    {
        if (in_array($sdg, $this->sdgFilter)) {
            $this->sdgFilter = array_values(array_filter($this->sdgFilter, fn ($s) => $s !== $sdg));
        } else {
            $this->sdgFilter[] = $sdg;
        }
        $this->page = 1;
    }

    /** Flip the sort direction between asc and desc */
    public function toggleSortDir(): void
    {
        $this->sortDir = $this->sortDir === 'desc' ? 'asc' : 'desc';
        $this->page    = 1;
    }

    /** Reset every filter (not search) to its default state */
    public function clearAllFilters(): void
    {
        $this->typeFilter    = '';
        $this->formatFilter  = '';
        $this->pubDateFrom   = '';
        $this->pubDateTo     = '';
        $this->sdgFilter     = [];
        $this->availableOnly = false;
        $this->page          = 1;
    }
    /* ──────────────────────────────────────────────────────────────────── */

    // ── Query ───────────────────────────────────────────────────────────────
    protected function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user      = Auth::user();
        $userLevel = UserRole::from($user->role)->getAccessLevel();

        return RrMaterialParents::query()
            ->where('access_level', '<=', $userLevel)

            // ── Search (scope-aware) ────────────────────────────────────────
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    match ($this->searchScope) {
                        'title'   => $inner->where('title', 'like', $term),
                        'author'  => $inner->where('author', 'like', $term),
                        /* SQLite: JSON stored as text — LIKE on the raw column is sufficient
                           for partial keyword search and is compatible with SQLite. */
                        'keyword' => $inner->where('keywords', 'like', $term),
                        default   => $inner
                            ->where('title', 'like', $term)
                            ->orWhere('author', 'like', $term)
                            ->orWhere('keywords', 'like', $term),
                    };
                });
            })

            // ── Material type ───────────────────────────────────────────────
            ->when($this->typeFilter !== '', fn ($q) => $q->where('material_type', $this->typeFilter))

            // ── Format (digital / physical) ─────────────────────────────────
            ->when($this->formatFilter === 'digital', fn ($q) =>
                $q->whereHas('materials', fn ($m) =>
                    $m->where('is_digital', true)->where('is_available', true)->whereNull('deleted_at')
                )
            )
            ->when($this->formatFilter === 'physical', fn ($q) =>
                $q->whereHas('materials', fn ($m) =>
                    $m->where('is_digital', false)->where('is_available', true)->whereNull('deleted_at')
                )
            )

            /* publication_date range (full date precision) ──────────────────── */
            ->when($this->pubDateFrom !== '', fn ($q) =>
                $q->whereDate('publication_date', '>=', $this->pubDateFrom)
            )
            ->when($this->pubDateTo !== '', fn ($q) =>
                $q->whereDate('publication_date', '<=', $this->pubDateTo)
            )

            /* SDG multi-select (OR logic) ────────────────────────────────────
               SQLite stores JSON as text. Wrapping the value in `"…"` anchors
               the match to a JSON string boundary, avoiding false positives
               (e.g. "Poverty" matching "No Poverty Reduction"). */
            ->when(!empty($this->sdgFilter), function ($q) {
                $q->where(function ($inner) {
                    foreach ($this->sdgFilter as $sdg) {
                        $inner->orWhere('sdgs', 'like', '%"' . addslashes($sdg) . '"%');
                    }
                });
            })

            /* availability guard ─────────────────────────────────────── */
            ->when($this->availableOnly, fn ($q) =>
                $q->whereHas('materials', fn ($m) =>
                    $m->where('is_available', true)->whereNull('deleted_at')
                )
            )

            /* dynamic sort ───────────────────────────────────────────── */
            ->orderBy($this->sortBy, $this->sortDir);
    }

    // ── View data ───────────────────────────────────────────────────────────
    protected function getViewData(): array
    {
        $paginator = $this->getQuery()->paginate($this->perPage, ['*'], 'page', $this->page);

        $materials = collect($paginator->items())->map(fn (RrMaterialParents $m) => [
            'id'               => $m->id,
            'title'            => $m->title,
            'author'           => $m->author,
            'material_type'    => $m->material_type,
            'access_level'     => $m->access_level,
            'publication_date' => $m->publication_date?->format('Y'),
            'keywords'         => is_array($m->keywords) ? implode(', ', array_slice($m->keywords, 0, 3)) : '',
            'has_digital'      => $m->materials()->where('is_digital', true)->where('is_available', true)->whereNull('deleted_at')->exists(),
            'has_physical'     => $m->materials()->where('is_digital', false)->where('is_available', true)->whereNull('deleted_at')->exists(),
            'view_url'         => CatalogResource::getUrl('view', ['record' => $m->id]),
        ])->toArray();

        /* compute active filter count for the badge on the filter toggle */
        $activeFilterCount = collect([
            $this->typeFilter    !== '',
            $this->formatFilter  !== '',
            $this->pubDateFrom   !== '',
            $this->pubDateTo     !== '',
            ! empty($this->sdgFilter),
            $this->availableOnly,
        ])->filter()->count();

        return [
            'materials'         => $materials,
            'paginator'         => $paginator,
            'totalResults'      => $paginator->total(),
            'activeFilterCount' => $activeFilterCount,
        ];
    }
}