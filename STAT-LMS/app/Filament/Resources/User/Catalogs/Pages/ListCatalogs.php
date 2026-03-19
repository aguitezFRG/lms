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

    // ── Query ───────────────────────────────────────────────────────────────
    protected function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user      = Auth::user();
        $userLevel = UserRole::from($user->role)->getAccessLevel();

        return RrMaterialParents::query()
            ->where('access_level', '<=', $userLevel)
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                          ->orWhere('author', 'like', $term)
                          ->orWhereRaw("JSON_SEARCH(keywords, 'one', ?) IS NOT NULL", [$this->search]);
                });
            })
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
            )
            ->orderByDesc('created_at');
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