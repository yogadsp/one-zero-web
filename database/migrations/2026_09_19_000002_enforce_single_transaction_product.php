<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TRIGGER transactions_single_product_insert
                BEFORE INSERT ON transactions
                WHEN (NEW.exam_id IS NULL) = (NEW.learning_material_id IS NULL)
                BEGIN
                    SELECT RAISE(ABORT, 'transaction must reference exactly one product');
                END
            SQL);
            DB::statement(<<<'SQL'
                CREATE TRIGGER transactions_single_product_update
                BEFORE UPDATE OF exam_id, learning_material_id ON transactions
                WHEN (NEW.exam_id IS NULL) = (NEW.learning_material_id IS NULL)
                BEGIN
                    SELECT RAISE(ABORT, 'transaction must reference exactly one product');
                END
            SQL);

            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER transactions_single_product_insert
            BEFORE INSERT ON transactions
            FOR EACH ROW
            BEGIN
                IF ((NEW.exam_id IS NULL) = (NEW.learning_material_id IS NULL)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'transaction must reference exactly one product';
                END IF;
            END
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER transactions_single_product_update
            BEFORE UPDATE ON transactions
            FOR EACH ROW
            BEGIN
                IF ((NEW.exam_id IS NULL) = (NEW.learning_material_id IS NULL)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'transaction must reference exactly one product';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS transactions_single_product_insert');
            DB::statement('DROP TRIGGER IF EXISTS transactions_single_product_update');

            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS transactions_single_product_insert');
        DB::statement('DROP TRIGGER IF EXISTS transactions_single_product_update');
    }
};
