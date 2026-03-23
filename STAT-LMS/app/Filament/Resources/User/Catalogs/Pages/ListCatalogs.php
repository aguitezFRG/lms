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

    protected $queryString = [
        'search'       => ['except' => ''],
        'typeFilter'   => ['except' => ''],
        'formatFilter' => ['except' => ''],
        'page'         => ['except' => 1],
    ];

    // ── Livewire lifecycle ──────────────────────────────────────────────────
    public function updatedSearch(): void       { $this->page = 1; }
    public function updatedTypeFilter(): void   { $this->page = 1; }
    public function updatedFormatFilter(): void { $this->page = 1; }

    public function nextPage(): void       { $this->page++; }
    public function previousPage(): void   { if ($this->page > 1) $this->page--; }
    public function goToPage(int $p): void { $this->page = $p; }

    // ── OPAC Search ─────────────────────────────────────────────────────────

    /**
     * Parse a raw OPAC query string into structured tokens.
     *
     * Supported syntax:
     *   - Multi-term AND   : each whitespace-separated token must match
     *   - Quoted phrases   : "exact phrase" treated as a single token
     *   - Field prefixes   : ti: au: kw: ab: adv: (and long-form aliases)
     *
     * Examples:
     *   regression analysis
     *   "time series" au:santos
     *   ti:bayesian kw:inference
     *
     * @return array<int, array{term: string, field: string|null}>
     */
    protected function parseSearchQuery(string $query): array
    {
        preg_match_all('/"([^"]+)"|(\S+)/', trim($query), $matches);

        $tokens = [];

        foreach ($matches[0] as $i => $raw) {
            $value = $matches[1][$i] !== '' ? $matches[1][$i] : $matches[2][$i];
            $field = null;

            // Detect field prefix — e.g. ti:regression  or  au:"dela Cruz"
            if (preg_match(
                '/^(ti|title|au|author|kw|keyword|keywords|ab|abstract|adv|adviser):(.+)$/i',
                $value,
                $fm
            )) {
                $prefix = strtolower($fm[1]);
                $field  = match ($prefix) {
                    'ti', 'title'               => 'title',
                    'au', 'author'              => 'author',
                    'kw', 'keyword', 'keywords' => 'keywords',
                    'ab', 'abstract'            => 'abstract',
                    'adv', 'adviser'            => 'adviser',
                    default                     => null,
                };
                $value = $fm[2];
            }

            if ($value !== '') {
                $tokens[] = ['term' => $value, 'field' => $field];
            }
        }

        return $tokens;
    }

    /**
     * Apply OPAC-style search to the query builder.
     *
     * AND logic  — every token must match at least one searchable field
     *              (or the specific field if a prefix was given).
     *
     * Relevance  — when there is exactly one unqualified token, results are
     *              ordered by field priority: title > author > keywords >
     *              adviser > abstract.
     */
    protected function applyOPACSearch(
        \Illuminate\Database\Eloquent\Builder $q,
        string $raw
    ): \Illuminate\Database\Eloquent\Builder {
        $tokens = $this->parseSearchQuery($raw);

        if (empty($tokens)) {
            return $q;
        }

        foreach ($tokens as $token) {
            $like  = '%' . $token['term'] . '%';
            $field = $token['field'];

            $q->where(function ($inner) use ($like, $field) {
                if ($field) {
                    // Targeted field search
                    $inner->where($field, 'like', $like);
                } else {
                    // Broad search across all indexed fields
                    $inner->where('title',    'like', $like)
                          ->orWhere('author',   'like', $like)
                          ->orWhere('keywords', 'like', $like)
                          ->orWhere('adviser',  'like', $like)
                          ->orWhere('abstract', 'like', $like);
                }
            });
        }

        // Relevance ordering for a single unqualified term
        $unqualified = array_values(array_filter($tokens, fn ($t) => $t['field'] === null));

        if (count($unqualified) === 1) {
            $like = '%' . $unqualified[0]['term'] . '%';

            $q->orderByRaw("
                CASE
                    WHEN title    LIKE ? THEN 1
                    WHEN author   LIKE ? THEN 2
                    WHEN keywords LIKE ? THEN 3
                    WHEN adviser  LIKE ? THEN 4
                    ELSE                      5
                END
            ", [$like, $like, $like, $like]);
        } else {
            $q->orderByDesc('created_at');
        }

        return $q;
    }

    // ── Query ───────────────────────────────────────────────────────────────
    protected function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user      = Auth::user();
        $userLevel = UserRole::from($user->role)->getAccessLevel();

        $query = RrMaterialParents::query()
            ->where('access_level', '<=', $userLevel)
            ->when($this->typeFilter !== '', fn ($q) => $q->where('material_type', $this->typeFilter))
            ->when($this->formatFilter === 'digital', fn ($q) =>
                $q->whereHas('materials', fn ($m) =>
                    $m->where('is_digital', true)->where('is_available', true)->whereNull('deleted_at')
                )
            )
            ->when($this->formatFilter === 'physical', fn ($q) =>
                $q->whereHas('materials', fn ($m) =>
                    $m->where('is_digital', false)->where('is_available', true)->whereNull('deleted_at')
                )
            );

        if ($this->search !== '') {
            $query = $this->applyOPACSearch($query, $this->search);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query;
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

        return [
            'materials'    => $materials,
            'paginator'    => $paginator,
            'totalResults' => $paginator->total(),
        ];
    }
}