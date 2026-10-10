<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const CATEGORY_IDS = [
        'CPNS' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96e000',
        'UTBK' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96e001',
        'TKA' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96e002',
    ];

    private const VALUE_CATEGORY_IDS = [
        'CPNS' => [
            'TWK' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f001',
            'TIU' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f002',
            'TKP' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f003',
        ],
        'UTBK' => [
            'Penalaran Umum' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f101',
            'Pengetahuan dan Pemahaman Umum' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f102',
            'Pemahaman Bacaan dan Menulis' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f103',
            'Pengetahuan Kuantitatif' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f104',
            'Literasi Bahasa Indonesia' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f105',
            'Literasi Bahasa Inggris' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f106',
            'Penalaran Matematika' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f107',
        ],
        'TKA' => [
            'Bahasa Indonesia' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f201',
            'Matematika' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f202',
            'Bahasa Inggris' => '9b8ea8d8-a4d1-4a7d-a032-8dd9ae96f203',
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::CATEGORY_IDS as $name => $id) {
            $exists = DB::table('categories')->whereRaw('UPPER(name) = ?', [$name])->exists();

            if (! $exists) {
                DB::table('categories')->insert([
                    'id' => $id,
                    'name' => $name,
                    'thumbnail' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }

        foreach (self::VALUE_CATEGORY_IDS as $program => $sections) {
            $category = DB::table('categories')
                ->whereNull('deleted_at')
                ->whereRaw('UPPER(name) = ?', [$program])
                ->first();

            if (! $category || DB::table('value_categories')->where('category_id', $category->id)->whereNull('deleted_at')->exists()) {
                continue;
            }

            foreach (array_keys($sections) as $index => $name) {
                DB::table('value_categories')->insert([
                    'id' => $sections[$name] ?? (string) Str::uuid(),
                    'category_id' => $category->id,
                    'name' => $name,
                    'assessment_type' => $program === 'CPNS' ? 3 : 1,
                    'section' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('value_categories')
            ->whereIn('id', collect(self::VALUE_CATEGORY_IDS)->flatMap(fn (array $ids) => array_values($ids))->all())
            ->delete();

        DB::table('categories')->whereIn('id', array_values(self::CATEGORY_IDS))->delete();
    }
};
