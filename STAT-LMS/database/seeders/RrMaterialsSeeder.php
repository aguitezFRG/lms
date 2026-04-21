<?php

namespace Database\Seeders;

use App\Models\RrMaterials;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RrMaterialsSeeder extends Seeder
{
    /*
     * Pre-determined UUIDs
     *
     * Pattern: 33333333-3333-3333-3333-0000000000{nn}
     */
    // Digital copies (ordered by parent UUID suffix: 01, 02, 03, 04, 06, 07)
    public const STATS_BOOK_DIGITAL_ID = '33333333-3333-3333-3333-000000000001';

    public const PSA_JOURNAL_DIGITAL_ID = '33333333-3333-3333-3333-000000000002';

    public const APPLIED_STATS_DIGITAL_ID = '33333333-3333-3333-3333-000000000003';

    public const TIMESERIES_DIGITAL_ID = '33333333-3333-3333-3333-000000000004';

    public const BAYESIAN_DIGITAL_ID = '33333333-3333-3333-3333-000000000005';

    public const ML_JOURNAL_DIGITAL_ID = '33333333-3333-3333-3333-000000000006';

    // Physical copies
    public const STATS_BOOK_PHYSICAL_ID = '33333333-3333-3333-3333-000000000007';

    public const STATS_BOOK_PHYSICAL_2_ID = '33333333-3333-3333-3333-000000000008';

    public const PSA_JOURNAL_PHYSICAL_ID = '33333333-3333-3333-3333-000000000009';

    public const BAYESIAN_PHYSICAL_ID = '33333333-3333-3333-3333-00000000000a';

    public const REGRESSION_PHYSICAL_ID = '33333333-3333-3333-3333-00000000000b';

    // Backward compatibility alias (regression thesis no longer has digital copy)
    public const REGRESSION_DIGITAL_ID = self::REGRESSION_PHYSICAL_ID;

    public const MULTIVARIATE_PHYSICAL_ID = '33333333-3333-3333-3333-00000000000c';

    // Backward compatibility alias (was renamed to ML_JOURNAL_DIGITAL_ID)
    public const ML_THESIS_DIGITAL_ID = self::ML_JOURNAL_DIGITAL_ID;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Map of parent IDs to their access levels and digital copy info
        $digitalCopies = [
            // Parent 01: STATS_BOOK_ID (access_level: 1)
            [
                'copy_id' => self::STATS_BOOK_DIGITAL_ID,
                'parent_id' => RrMaterialParentsSeeder::STATS_BOOK_ID,
                'access_level' => 1,
                'pdf_file' => '10.5117_9789462985100_previewpdf.pdf',
            ],
            // Parent 02: PSA_JOURNAL_ID (access_level: 1)
            [
                'copy_id' => self::PSA_JOURNAL_DIGITAL_ID,
                'parent_id' => RrMaterialParentsSeeder::PSA_JOURNAL_ID,
                'access_level' => 1,
                'pdf_file' => '292-Article Text-1045-1-10-20210812.pdf',
            ],
            // Parent 03: APPLIED_STATS_BOOK_ID (access_level: 1)
            [
                'copy_id' => self::APPLIED_STATS_DIGITAL_ID,
                'parent_id' => RrMaterialParentsSeeder::APPLIED_STATS_BOOK_ID,
                'access_level' => 1,
                'pdf_file' => 'An_Insight_in_Statistical_Techniques_and.pdf',
            ],
            // Parent 04: TIMESERIES_JOURNAL_ID (access_level: 1)
            [
                'copy_id' => self::TIMESERIES_DIGITAL_ID,
                'parent_id' => RrMaterialParentsSeeder::TIMESERIES_JOURNAL_ID,
                'access_level' => 1,
                'pdf_file' => '1-s2.0-S2211379720321136-main.pdf',
            ],
            // Parent 06: BAYESIAN_THESIS_ID (access_level: 2)
            [
                'copy_id' => self::BAYESIAN_DIGITAL_ID,
                'parent_id' => RrMaterialParentsSeeder::BAYESIAN_THESIS_ID,
                'access_level' => 2,
                'pdf_file' => 's12874-021-01432-5.pdf',
            ],
            // Parent 07: ML_THESIS_ID (access_level: 2)
            [
                'copy_id' => self::ML_JOURNAL_DIGITAL_ID,
                'parent_id' => RrMaterialParentsSeeder::ML_THESIS_ID,
                'access_level' => 2,
                'pdf_file' => 'Rice_Yield_Modeling_Using_Machine_Learni.pdf',
            ],
        ];

        // Create digital copies and copy PDF files to storage
        foreach ($digitalCopies as $copy) {
            $dir = "repository/access_level_{$copy['access_level']}";
            $targetPath = "{$dir}/{$copy['pdf_file']}";
            $sourcePath = database_path("seeders/.digital_copies/{$copy['pdf_file']}");

            // Create directory if it doesn't exist
            Storage::disk('local')->makeDirectory($dir);

            // Copy PDF file to storage
            Storage::disk('local')->put($targetPath, file_get_contents($sourcePath));

            // Create the RrMaterials record
            RrMaterials::factory()->create([
                'id' => $copy['copy_id'],
                'material_parent_id' => $copy['parent_id'],
                'is_digital' => true,
                'is_available' => true,
                'file_name' => $targetPath,
            ]);
        }

        // Create physical copies (includes physical-only and additional copies of digital parents)
        $physicalCopies = [
            // Parent 01: STATS_BOOK_ID (copy 1)
            [
                'copy_id' => self::STATS_BOOK_PHYSICAL_ID,
                'parent_id' => RrMaterialParentsSeeder::STATS_BOOK_ID,
            ],
            // Parent 01: STATS_BOOK_ID (copy 2 — for overdue borrow event)
            [
                'copy_id' => self::STATS_BOOK_PHYSICAL_2_ID,
                'parent_id' => RrMaterialParentsSeeder::STATS_BOOK_ID,
            ],
            // Parent 02: PSA_JOURNAL_ID (physical copy)
            [
                'copy_id' => self::PSA_JOURNAL_PHYSICAL_ID,
                'parent_id' => RrMaterialParentsSeeder::PSA_JOURNAL_ID,
            ],
            // Parent 06: BAYESIAN_THESIS_ID (physical copy)
            [
                'copy_id' => self::BAYESIAN_PHYSICAL_ID,
                'parent_id' => RrMaterialParentsSeeder::BAYESIAN_THESIS_ID,
            ],
            // Parent 05: REGRESSION_THESIS_ID (physical-only, no digital copy)
            [
                'copy_id' => self::REGRESSION_PHYSICAL_ID,
                'parent_id' => RrMaterialParentsSeeder::REGRESSION_THESIS_ID,
            ],
            // Parent 08: MULTIVARIATE_DISS_ID (physical-only, no digital copy)
            [
                'copy_id' => self::MULTIVARIATE_PHYSICAL_ID,
                'parent_id' => RrMaterialParentsSeeder::MULTIVARIATE_DISS_ID,
            ],
        ];

        foreach ($physicalCopies as $copy) {
            RrMaterials::factory()->create([
                'id' => $copy['copy_id'],
                'material_parent_id' => $copy['parent_id'],
                'is_digital' => false,
                'is_available' => true,
                'file_name' => null,
            ]);
        }
    }
}
