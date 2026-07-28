<?php

namespace Tests\Feature;

use App\Filament\Resources\User\Catalogs\Pages\ListCatalogs;
use App\Filament\Widgets\SystemUsage\MaterialStatisticsWidget;
use App\Filament\Widgets\SystemUsage\MonthlyTrendTableWidget;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use Closure;
use Filament\Tables\Table;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostgreSqlCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function catalog_json_filters_compile_to_postgresql_json_containment(): void
    {
        $student = $this->makeUser('student');
        $this->actingAs($student);

        $this->withPostgreSqlDefault(function (): void {
            $page = new InspectableListCatalogs;
            $page->availableOnly = false;
            $page->adviserFilter = 'Dr. Reyes';
            $page->sdgFilter = ['Quality Education', 'Climate Action'];

            $query = $page->inspectFilteredQuery();
            $sql = $query->toSql();

            $this->assertSame(3, substr_count($sql, '@>'));
            $this->assertStringNotContainsStringIgnoringCase(' like ', $sql);
            $this->assertStringNotContainsStringIgnoringCase('json_contains', $sql);
            $this->assertContains('"Dr. Reyes"', $query->getBindings());
            $this->assertContains('"Quality Education"', $query->getBindings());
            $this->assertContains('"Climate Action"', $query->getBindings());
        });
    }

    #[Test]
    public function dashboard_queries_compile_with_postgresql_date_expressions(): void
    {
        $this->withPostgreSqlDefault(function (Connection $connection): void {
            $monthlyWidget = new MonthlyTrendTableWidget;
            $monthlyQuery = $monthlyWidget
                ->table(Table::make($monthlyWidget))
                ->getQuery();

            $this->assertNotNull($monthlyQuery);

            $monthlySql = $monthlyQuery->toSql();

            $this->assertStringContainsStringIgnoringCase(
                "to_char(created_at, 'YYYY-MM')",
                $monthlySql,
            );
            $this->assertStringNotContainsStringIgnoringCase('date_format(', $monthlySql);
            $this->assertStringNotContainsStringIgnoringCase('strftime(', $monthlySql);

            $statisticsWidget = new MaterialStatisticsWidget;
            $statisticsWidget->frames = [[
                'startMonth' => 8,
                'endMonth' => 12,
                'endYear' => 2026,
            ]];

            $statisticsWidget->getChartData();
            $statisticsSql = implode(' ', $connection->recordedQueries());

            $this->assertStringContainsStringIgnoringCase(
                'extract(year from created_at)::integer',
                $statisticsSql,
            );
            $this->assertStringNotContainsStringIgnoringCase('year(created_at)', $statisticsSql);
            $this->assertStringNotContainsStringIgnoringCase('strftime(', $statisticsSql);
        });
    }

    #[Test]
    public function postgresql_migration_uses_a_boolean_partial_unique_index(): void
    {
        $this->withPostgreSqlDefault(function (Connection $connection): void {
            $migration = require database_path(
                'migrations/2026_04_24_000001_add_unique_digital_copy_per_parent_index.php',
            );

            $migration->up();
            $upSql = implode(' ', $connection->recordedQueries());

            $this->assertStringContainsStringIgnoringCase(
                'create unique index rr_materials_one_digital_per_parent',
                $upSql,
            );
            $this->assertStringContainsStringIgnoringCase(
                'where is_digital = true and deleted_at is null',
                preg_replace('/\s+/', ' ', $upSql) ?? $upSql,
            );
            $this->assertStringNotContainsStringIgnoringCase('create trigger', $upSql);

            $connection->resetRecordedQueries();
            $migration->down();
            $downSql = implode(' ', $connection->recordedQueries());

            $this->assertStringContainsStringIgnoringCase(
                'drop index if exists rr_materials_one_digital_per_parent',
                $downSql,
            );
            $this->assertStringNotContainsStringIgnoringCase('drop trigger', $downSql);
        });
    }

    #[Test]
    public function one_active_digital_copy_constraint_is_enforced_by_the_test_database(): void
    {
        $parent = RrMaterialParents::factory()->create();

        $firstCopy = RrMaterials::factory()->create([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
        ]);

        try {
            RrMaterials::factory()->create([
                'material_parent_id' => $parent->id,
                'is_digital' => true,
                'is_available' => true,
            ]);

            $this->fail('A second active digital copy should violate the unique constraint.');
        } catch (QueryException) {
            $this->assertSame(1, RrMaterials::query()
                ->where('material_parent_id', $parent->id)
                ->where('is_digital', true)
                ->count());
        }

        $firstCopy->delete();

        $replacement = RrMaterials::factory()->create([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
        ]);

        $this->assertNotNull($replacement->id);
    }

    private function withPostgreSqlDefault(Closure $callback): mixed
    {
        $originalConnection = config('database.default');
        $databaseManager = $this->app['db'];
        $recordingConnection = new RecordingPostgresConnection(
            static fn () => throw new \LogicException('The recording connection must not open a database socket.'),
            'testing',
            '',
            ['driver' => 'pgsql'],
        );

        $databaseManager->extend('pgsql', fn (): RecordingPostgresConnection => $recordingConnection);
        config(['database.default' => 'pgsql']);
        DB::setDefaultConnection('pgsql');
        DB::purge('pgsql');

        try {
            return $callback(DB::connection('pgsql'));
        } finally {
            DB::purge('pgsql');
            $databaseManager->forgetExtension('pgsql');
            config(['database.default' => $originalConnection]);
            DB::setDefaultConnection($originalConnection);
        }
    }
}

class InspectableListCatalogs extends ListCatalogs
{
    public function inspectFilteredQuery(): Builder
    {
        return $this->getQuery();
    }
}

class RecordingPostgresConnection extends PostgresConnection
{
    /**
     * @var list<string>
     */
    private array $recordedQueries = [];

    public function select($query, $bindings = [], $useReadPdo = true): array
    {
        $this->recordedQueries[] = $query;

        return [];
    }

    public function statement($query, $bindings = []): bool
    {
        $this->recordedQueries[] = $query;

        return true;
    }

    public function unprepared($query): bool
    {
        $this->recordedQueries[] = $query;

        return true;
    }

    /**
     * @return list<string>
     */
    public function recordedQueries(): array
    {
        return $this->recordedQueries;
    }

    public function resetRecordedQueries(): void
    {
        $this->recordedQueries = [];
    }
}
