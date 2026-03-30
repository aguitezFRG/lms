<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\MaterialAccessEvents;

class MaterialAccessEventsSeeder extends Seeder
{
    /*
     * Pre-determined UUIDs
     *
     * Pattern: 44444444-4444-4444-4444-0000000000{nn}
     *
     * Quick reference:
     *  ...0001  Carlos  — PENDING  request  (stats book digital)
     *  ...0002  Carlos  — APPROVED borrow   (PSA journal physical, due +5d)
     *  ...0003  Angelica— PENDING  borrow   (stats book physical)
     *  ...0004  Ricardo — APPROVED request  (regression thesis digital, faculty access)
     *  ...0005  Rafael  — REJECTED request  (stats book digital)
     *  ...0006  Carlos  — CANCELLED request (applied stats book digital)
     *  ...0007  Angelica— COMPLETED request (time series journal digital)
     *  ...0008  Rafael  — PENDING  borrow   (bayesian thesis physical)
     *  ...0009  Carlos  — APPROVED borrow   (stats book physical copy 2, OVERDUE)
     *  ...0010  Esperanza— APPROVED request (ML thesis digital, faculty access restricted)
     */

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [

            // 1. Carlos — PENDING digital request (stats book)
            [
                'id'             => '44444444-4444-4444-4444-000000000001',
                'user_id'        => UserSeeder::STUDENT_1_ID,
                'rr_material_id' => RrMaterialsSeeder::STATS_BOOK_DIGITAL_ID,
                'approver_id'    => null,
                'event_type'     => 'request',
                'status'         => 'pending',
                'due_at'         => null,
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => null,
                'completed_at'   => null,
            ],

            // 2. Carlos — APPROVED borrow (PSA journal physical, due in 5 days)
            [
                'id'             => '44444444-4444-4444-4444-000000000002',
                'user_id'        => UserSeeder::STUDENT_1_ID,
                'rr_material_id' => RrMaterialsSeeder::PSA_JOURNAL_PHYSICAL_ID,
                'approver_id'    => UserSeeder::STAFF_ID,
                'event_type'     => 'borrow',
                'status'         => 'approved',
                'due_at'         => now()->addDays(5)->endOfDay(),
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => now()->subDays(2),
                'completed_at'   => null,
            ],

            // 3. Angelica — PENDING borrow (stats book physical copy 1)
            [
                'id'             => '44444444-4444-4444-4444-000000000003',
                'user_id'        => UserSeeder::STUDENT_2_ID,
                'rr_material_id' => RrMaterialsSeeder::STATS_BOOK_PHYSICAL_ID,
                'approver_id'    => null,
                'event_type'     => 'borrow',
                'status'         => 'pending',
                'due_at'         => null,
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => null,
                'completed_at'   => null,
            ],

            // 4. Ricardo (faculty) — APPROVED digital request (regression thesis, restricted)
            [
                'id'             => '44444444-4444-4444-4444-000000000004',
                'user_id'        => UserSeeder::FACULTY_1_ID,
                'rr_material_id' => RrMaterialsSeeder::REGRESSION_DIGITAL_ID,
                'approver_id'    => UserSeeder::COMMITTEE_ID,
                'event_type'     => 'request',
                'status'         => 'approved',
                'due_at'         => now()->addDays(14)->endOfDay(),
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => now()->subDays(7),
                'completed_at'   => null,
            ],

            // 5. Rafael — REJECTED digital request (stats book)
            [
                'id'             => '44444444-4444-4444-4444-000000000005',
                'user_id'        => UserSeeder::STUDENT_3_ID,
                'rr_material_id' => RrMaterialsSeeder::STATS_BOOK_DIGITAL_ID,
                'approver_id'    => UserSeeder::STAFF_ID,
                'event_type'     => 'request',
                'status'         => 'rejected',
                'due_at'         => null,
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => null,
                'completed_at'   => null,
            ],

            // 6. Carlos — CANCELLED digital request (applied stats book)
            [
                'id'             => '44444444-4444-4444-4444-000000000006',
                'user_id'        => UserSeeder::STUDENT_1_ID,
                'rr_material_id' => RrMaterialsSeeder::APPLIED_STATS_DIGITAL_ID,
                'approver_id'    => null,
                'event_type'     => 'request',
                'status'         => 'cancelled',
                'due_at'         => null,
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => null,
                'completed_at'   => null,
            ],

            // 7. Angelica — COMPLETED digital request (time series journal)
            [
                'id'             => '44444444-4444-4444-4444-000000000007',
                'user_id'        => UserSeeder::STUDENT_2_ID,
                'rr_material_id' => RrMaterialsSeeder::TIMESERIES_DIGITAL_ID,
                'approver_id'    => UserSeeder::COMMITTEE_ID,
                'event_type'     => 'request',
                'status'         => 'completed',
                'due_at'         => null,
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => now()->subDays(30),
                'completed_at'   => now()->subDays(15),
            ],

            // 8. Rafael — PENDING borrow (bayesian thesis physical)
            [
                'id'             => '44444444-4444-4444-4444-000000000008',
                'user_id'        => UserSeeder::STUDENT_3_ID,
                'rr_material_id' => RrMaterialsSeeder::BAYESIAN_PHYSICAL_ID,
                'approver_id'    => null,
                'event_type'     => 'borrow',
                'status'         => 'pending',
                'due_at'         => null,
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => null,
                'completed_at'   => null,
            ],

            // 9. Carlos — APPROVED borrow (stats book physical copy 2) — OVERDUE, not returned
            //    The copy is_available=false because this borrow is outstanding
            [
                'id'             => '44444444-4444-4444-4444-000000000009',
                'user_id'        => UserSeeder::STUDENT_1_ID,
                'rr_material_id' => RrMaterialsSeeder::STATS_BOOK_PHYSICAL_2_ID,
                'approver_id'    => UserSeeder::STAFF_ID,
                'event_type'     => 'borrow',
                'status'         => 'approved',
                'due_at'         => now()->subDays(3)->endOfDay(), // past due — already overdue
                'returned_at'    => null,
                'is_overdue'     => true, // pre-flagged; observer will confirm on first retrieval
                'approved_at'    => now()->subDays(17),
                'completed_at'   => null,
            ],

            // 10. Esperanza (faculty) — APPROVED digital request (ML thesis, restricted access)
            [
                'id'             => '44444444-4444-4444-4444-000000000010',
                'user_id'        => UserSeeder::FACULTY_2_ID,
                'rr_material_id' => RrMaterialsSeeder::ML_THESIS_DIGITAL_ID,
                'approver_id'    => UserSeeder::COMMITTEE_ID,
                'event_type'     => 'request',
                'status'         => 'approved',
                'due_at'         => now()->addDays(7)->endOfDay(),
                'returned_at'    => null,
                'is_overdue'     => false,
                'approved_at'    => now()->subDay(),
                'completed_at'   => null,
            ],

        ];

        foreach ($events as $data) {
            MaterialAccessEvents::factory()->create($data);
        }
    }
}
