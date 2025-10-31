<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert initial departments into the 'departement' table
        DB::table('departement')->insertOrIgnore([
            [
                'id' => '11911041',
                'name' => 'Manajemen',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '11911042',
                'name' => 'Ekonomi Pembangunan / IESP',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '11911043',
                'name' => 'Akutansi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
