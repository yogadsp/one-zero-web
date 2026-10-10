<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $readingCategoryIds = DB::table('value_categories')
            ->whereRaw('LOWER(name) = ?', ['reading comprehension'])
            ->pluck('id');

        DB::table('detail_value_categories')
            ->whereIn('value_category_id', $readingCategoryIds)
            ->where('min_grade', 36)
            ->where('max_grade', 35)
            ->where('final_grade', 52)
            ->update([
                'min_grade' => 35,
                'max_grade' => 36,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $readingCategoryIds = DB::table('value_categories')
            ->whereRaw('LOWER(name) = ?', ['reading comprehension'])
            ->pluck('id');

        DB::table('detail_value_categories')
            ->whereIn('value_category_id', $readingCategoryIds)
            ->where('min_grade', 35)
            ->where('max_grade', 36)
            ->where('final_grade', 52)
            ->update([
                'min_grade' => 36,
                'max_grade' => 35,
                'updated_at' => now(),
            ]);
    }
};
