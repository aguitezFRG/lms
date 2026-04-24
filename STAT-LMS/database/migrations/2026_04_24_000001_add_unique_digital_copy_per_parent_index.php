<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite supports partial indexes — simplest and most precise approach.
            DB::statement('CREATE UNIQUE INDEX rr_materials_one_digital_per_parent
                ON rr_materials (material_parent_id)
                WHERE is_digital = 1 AND deleted_at IS NULL');
        } else {
            // MariaDB does not support partial indexes, and MariaDB 10.6 does not allow
            // indexing virtual generated columns that use IF()/CASE WHEN expressions
            // (error 1901). Instead we enforce the constraint via BEFORE INSERT and
            // BEFORE UPDATE triggers.
            //
            // The triggers raise SQLSTATE 45000 when a second non-deleted digital copy
            // would be created for the same material_parent_id.
            DB::unprepared('
                CREATE TRIGGER rr_materials_unique_digital_before_insert
                BEFORE INSERT ON rr_materials
                FOR EACH ROW
                BEGIN
                    IF NEW.is_digital = 1 AND NEW.deleted_at IS NULL THEN
                        IF EXISTS (
                            SELECT 1 FROM rr_materials
                            WHERE material_parent_id = NEW.material_parent_id
                              AND is_digital = 1
                              AND deleted_at IS NULL
                        ) THEN
                            SIGNAL SQLSTATE \'45000\'
                                SET MESSAGE_TEXT = \'Only one active digital copy is allowed per material parent.\';
                        END IF;
                    END IF;
                END
            ');

            DB::unprepared('
                CREATE TRIGGER rr_materials_unique_digital_before_update
                BEFORE UPDATE ON rr_materials
                FOR EACH ROW
                BEGIN
                    IF NEW.is_digital = 1 AND NEW.deleted_at IS NULL THEN
                        IF EXISTS (
                            SELECT 1 FROM rr_materials
                            WHERE material_parent_id = NEW.material_parent_id
                              AND is_digital = 1
                              AND deleted_at IS NULL
                              AND id != NEW.id
                        ) THEN
                            SIGNAL SQLSTATE \'45000\'
                                SET MESSAGE_TEXT = \'Only one active digital copy is allowed per material parent.\';
                        END IF;
                    END IF;
                END
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS rr_materials_one_digital_per_parent');
        } else {
            DB::unprepared('DROP TRIGGER IF EXISTS rr_materials_unique_digital_before_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS rr_materials_unique_digital_before_update');
        }
    }
};
