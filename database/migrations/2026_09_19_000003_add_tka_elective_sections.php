<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SECTIONS = [
        '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f204' => ['Mata Pelajaran Pilihan 1', 4],
        '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f205' => ['Mata Pelajaran Pilihan 2', 5],
    ];

    public function up(): void
    {
        $categoryId = DB::table('categories')
            ->whereNull('deleted_at')
            ->whereRaw('UPPER(name) = ?', ['TKA'])
            ->value('id');

        if (! $categoryId) {
            return;
        }

        foreach (self::SECTIONS as $id => [$name, $section]) {
            if (DB::table('value_categories')->where('category_id', $categoryId)->where('section', $section)->whereNull('deleted_at')->exists()) {
                continue;
            }

            DB::table('value_categories')->insert([
                'id' => $id,
                'category_id' => $categoryId,
                'name' => $name,
                'assessment_type' => 1,
                'section' => $section,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('value_categories')->whereIn('id', array_keys(self::SECTIONS))->delete();
    }
};
