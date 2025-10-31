<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // bikin composite index
            $table->index(['exam_id', 'is_finished', 'grade_old'], 'idx_exam_finished_gradeold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $database = env('DB_DATABASE');
        $tableName = 'grades';
        $columnsToCheck = ['exam_id', 'is_finished', 'grade_old'];

        // cek apakah index ada
        $index = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->whereIn('COLUMN_NAME', $columnsToCheck)
            ->first();

        Schema::table('grades', function (Blueprint $table) use ($index) {
            if ($index) {
                // drop index dan foreign key hanya jika index/foreign key ada
                $table->dropForeign('grades_exam_id_foreign');
                $table->dropForeign('grades_category_id_foreign');
                $table->dropIndex('idx_exam_finished_gradeold');
            }

            // create lagi foreign key
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }
};
