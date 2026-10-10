<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildLearningMaterialsForSqlite();
            $this->rebuildTransactionsForSqlite();
        } else {
            $this->upgradeLearningMaterialsForMysql();
            $this->upgradeTransactionsForMysql();
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->index(['exam_id', 'user_id', 'created_at'], 'grades_exam_user_history_index');
        });
    }

    public function down(): void
    {
        if (DB::table('transactions')->whereNotNull('learning_material_id')->exists()
            || DB::table('learning_materials')->whereNull('exam_id')->exists()) {
            throw new RuntimeException(
                'Rollback dihentikan karena sudah ada pembelian atau materi mandiri. Ekspor data tersebut sebelum rollback.'
            );
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_exam_user_history_index');
        });

        if (DB::getDriverName() === 'sqlite') {
            throw new RuntimeException('Rollback SQLite memerlukan restore dari backup sebelum migrasi.');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('transactions_learning_material_id_foreign');
            $table->dropIndex('transactions_learning_material_access_index');
            $table->dropColumn('learning_material_id');
            $table->dropForeign('transactions_exam_id_foreign');
        });
        DB::statement('ALTER TABLE transactions MODIFY exam_id CHAR(36) NOT NULL');
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropForeign('learning_materials_category_id_foreign');
            $table->dropForeign('learning_materials_exam_id_foreign');
            $table->dropIndex('learning_materials_storefront_index');
            $table->dropColumn(['category_id', 'price', 'standalone_since']);
        });
        DB::statement('ALTER TABLE learning_materials MODIFY exam_id CHAR(36) NOT NULL');
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnUpdate()->cascadeOnDelete();
            $table->index(['exam_id', 'type', 'is_published'], 'learning_materials_catalog_index');
        });
    }

    private function upgradeLearningMaterialsForMysql(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->uuid('category_id')->nullable()->after('exam_id');
            $table->unsignedBigInteger('price')->nullable()->after('title');
            $table->dateTime('standalone_since')->nullable()->after('price');
        });

        DB::statement(<<<'SQL'
            UPDATE learning_materials AS material
            INNER JOIN exams AS exam ON exam.id = material.exam_id
            SET material.category_id = exam.category_id,
                material.standalone_since = CURRENT_TIMESTAMP
        SQL);

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropForeign('learning_materials_exam_id_foreign');
            $table->dropIndex('learning_materials_catalog_index');
        });
        DB::statement('ALTER TABLE learning_materials MODIFY exam_id CHAR(36) NULL');
        DB::statement('ALTER TABLE learning_materials MODIFY category_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE learning_materials MODIFY standalone_since DATETIME NOT NULL');

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['type', 'category_id', 'is_published', 'price'], 'learning_materials_storefront_index');
        });
    }

    private function upgradeTransactionsForMysql(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('learning_material_id')->nullable()->after('exam_id');
            $table->dropForeign('transactions_exam_id_foreign');
        });
        DB::statement('ALTER TABLE transactions MODIFY exam_id CHAR(36) NULL');

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('learning_material_id')
                ->references('id')
                ->on('learning_materials')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->index(
                ['user_id', 'learning_material_id', 'transaction_status', 'voucher_activated'],
                'transactions_learning_material_access_index'
            );
        });
    }

    private function rebuildLearningMaterialsForSqlite(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('learning_materials_v2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id')->nullable();
            $table->uuid('category_id');
            $table->string('type', 32);
            $table->string('title');
            $table->unsignedBigInteger('price')->nullable();
            $table->dateTime('standalone_since');
            $table->text('description')->nullable();
            $table->string('url', 2048);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('exam_id')->references('id')->on('exams')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete()->cascadeOnUpdate();
        });

        DB::statement(<<<'SQL'
            INSERT INTO learning_materials_v2
                (id, exam_id, category_id, type, title, price, standalone_since, description, url,
                 starts_at, ends_at, is_published, position, created_at, updated_at, deleted_at)
            SELECT material.id, material.exam_id, exam.category_id, material.type, material.title, NULL,
                   CURRENT_TIMESTAMP, material.description, material.url, material.starts_at, material.ends_at,
                   material.is_published, material.position, material.created_at, material.updated_at, material.deleted_at
            FROM learning_materials AS material
            INNER JOIN exams AS exam ON exam.id = material.exam_id
        SQL);

        Schema::drop('learning_materials');
        Schema::rename('learning_materials_v2', 'learning_materials');
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->index(['type', 'category_id', 'is_published', 'price'], 'learning_materials_storefront_index');
        });
        Schema::enableForeignKeyConstraints();
    }

    private function rebuildTransactionsForSqlite(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('transactions_v2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('user_id');
            $table->uuid('exam_id')->nullable();
            $table->uuid('learning_material_id')->nullable();
            $table->string('code');
            $table->tinyInteger('voucher_activated')->default(0);
            $table->tinyInteger('voucher_used')->default(0);
            $table->double('total_purchases');
            $table->dateTime('maximum_payment_time');
            $table->string('transaction_status');
            $table->string('voucher_token')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('exam_id')->references('id')->on('exams')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('learning_material_id')->references('id')->on('learning_materials')->restrictOnDelete()->cascadeOnUpdate();
        });

        DB::statement(<<<'SQL'
            INSERT INTO transactions_v2
                (id, created_at, updated_at, deleted_at, user_id, exam_id, learning_material_id, code,
                 voucher_activated, voucher_used, total_purchases, maximum_payment_time,
                 transaction_status, voucher_token)
            SELECT id, created_at, updated_at, deleted_at, user_id, exam_id, NULL, code,
                   voucher_activated, voucher_used, total_purchases, maximum_payment_time,
                   transaction_status, voucher_token
            FROM transactions
        SQL);

        Schema::drop('transactions');
        Schema::rename('transactions_v2', 'transactions');
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['user_id', 'exam_id', 'transaction_status', 'voucher_activated'],
                'transactions_learning_access_index'
            );
            $table->index(
                ['user_id', 'learning_material_id', 'transaction_status', 'voucher_activated'],
                'transactions_learning_material_access_index'
            );
        });
        Schema::enableForeignKeyConstraints();
    }
};
