<?php

namespace Database\Seeders;

use App\Models\RrMaterials;
use Illuminate\Database\Seeder;

class RrMaterialsSeeder extends Seeder
{
    /*
     * Pre-determined UUIDs
     *
     * Pattern: 33333333-3333-3333-3333-0000000000{nn}
     */
    public const STATS_BOOK_DIGITAL_ID = '33333333-3333-3333-3333-000000000001';

    public const STATS_BOOK_PHYSICAL_ID = '33333333-3333-3333-3333-000000000002';

    public const REGRESSION_DIGITAL_ID = '33333333-3333-3333-3333-000000000003';

    public const PSA_JOURNAL_PHYSICAL_ID = '33333333-3333-3333-3333-000000000004';

    public const MULTIVARIATE_DIGITAL_ID = '33333333-3333-3333-3333-000000000005';

    public const APPLIED_STATS_DIGITAL_ID = '33333333-3333-3333-3333-000000000006';

    public const BAYESIAN_PHYSICAL_ID = '33333333-3333-3333-3333-000000000007';

    public const TIMESERIES_DIGITAL_ID = '33333333-3333-3333-3333-000000000008';

    public const STATS_BOOK_PHYSICAL_2_ID = '33333333-3333-3333-3333-000000000009';

    public const ML_THESIS_DIGITAL_ID = '33333333-3333-3333-3333-000000000010';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $copies = [

            // ── Stats Book (parent ...0001) ────────────────────────────────────
            [
                'id' => self::STATS_BOOK_DIGITAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::STATS_BOOK_ID,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'repository/access_level_1/book_introduction-mathematical-statistics-2019-a1b2c3d4e5f6-v1.pdf',
            ],
            [
                'id' => self::STATS_BOOK_PHYSICAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::STATS_BOOK_ID,
                'is_digital' => false,
                'is_available' => true,
                'file_name' => null,
            ],
            // Second physical copy — currently checked out (Carlos has it, overdue)
            [
                'id' => self::STATS_BOOK_PHYSICAL_2_ID,
                'material_parent_id' => RrMaterialParentsSeeder::STATS_BOOK_ID,
                'is_digital' => false,
                'is_available' => false,
                'file_name' => null,
            ],

            // ── Regression Thesis (parent ...0002) ─────────────────────────────
            [
                'id' => self::REGRESSION_DIGITAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::REGRESSION_THESIS_ID,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'repository/access_level_2/thesis_regression-rice-yield-luzon-2023-b2c3d4e5f6a7-v1.pdf',
            ],

            // ── PSA Journal (parent ...0003) ───────────────────────────────────
            [
                'id' => self::PSA_JOURNAL_PHYSICAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::PSA_JOURNAL_ID,
                'is_digital' => false,
                'is_available' => true,
                'file_name' => null,
            ],

            // ── Multivariate Dissertation (parent ...0004) ─────────────────────
            [
                'id' => self::MULTIVARIATE_DIGITAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::MULTIVARIATE_DISS_ID,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'repository/access_level_3/dissertation_multivariate-socioeconomic-2021-c3d4e5f6a7b8-v1.pdf',
            ],

            // ── Applied Stats Book (parent ...0005) ───────────────────────────
            [
                'id' => self::APPLIED_STATS_DIGITAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::APPLIED_STATS_BOOK_ID,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'repository/access_level_1/book_applied-statistical-methods-agricultural-2018-d4e5f6a7b8c9-v1.pdf',
            ],

            // ── Bayesian Thesis (parent ...0006) ──────────────────────────────
            [
                'id' => self::BAYESIAN_PHYSICAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::BAYESIAN_THESIS_ID,
                'is_digital' => false,
                'is_available' => true,
                'file_name' => null,
            ],

            // ── Time Series Journal (parent ...0007) ──────────────────────────
            [
                'id' => self::TIMESERIES_DIGITAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::TIMESERIES_JOURNAL_ID,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'repository/access_level_1/journal_time-series-covid19-philippines-2021-e5f6a7b8c9d0-v1.pdf',
            ],

            // ── ML Thesis (parent ...0008) ────────────────────────────────────
            [
                'id' => self::ML_THESIS_DIGITAL_ID,
                'material_parent_id' => RrMaterialParentsSeeder::ML_THESIS_ID,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'repository/access_level_2/thesis_machine-learning-agricultural-yield-2023-f6a7b8c9d0e1-v1.pdf',
            ],
        ];

        foreach ($copies as $data) {
            RrMaterials::factory()->create($data);
        }
    }
}
