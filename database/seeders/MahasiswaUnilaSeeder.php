<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class MahasiswaUnilaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        $examID = '49e1686d-0433-4d68-8040-9019ce3ef0e2';

        $path = database_path('seeders/data/data_mahasiswa_feb_unila.csv');
        $file = fopen($path, 'r');

        // baca header
        $header = fgetcsv($file, 0, ',');
        $header = array_map('trim', $header); // hilangin spasi/enter di header

        while (($row = fgetcsv($file, 0, ',')) !== false) {
            if (count($header) !== count($row)) {
                dd('Jumlah kolom header dan row tidak sama', $header, $row);
            }


            $data = array_combine($header, $row);

            // debug dulu semua kolom biar yakin mapping benar


            // id user
            $userID = Str::uuid()->toString();

            // id mahasiswa
            $studentID = Str::uuid()->toString();

            // id transaksi
            $transactionID = Str::uuid()->toString();

            // dd($data, $userID, $studentID, $transactionID);

            DB::table('users')->insertOrIgnore([
                'id' => $userID,
                'name' => $data['name'] ?? null,       // pastikan sesuai header CSV
                'email' => $data['email'] ?? null,     // pastikan sesuai header CSV
                'email_verified_at' => now(),
                'password' => bcrypt('LET12345'),
                'level' => 2,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('students')->insertOrIgnore([
                'id' => $studentID,
                'user_id' => $userID,
                'department_id' => $data['department'] ?? null, // pastikan sesuai header CSV
                'province_id' => '18',
                'city_id' => '1871',
                'district_id' => '187110',
                'village_id' => '24493',
                'address' => 'Bandar Lampung',
                'phone_number' => '08111111111',
                'gender' => 'M',
                'is_member' => 1,
                'created_at' => now(),
            ]);

            DB::table('transactions')->insertOrIgnore([
                'id' => $transactionID,
                'user_id' => $userID,
                'exam_id' => $examID,
                'code' => 'INV.230528.0001',
                'voucher_activated' => 1,
                'voucher_used' => 1,
                'total_purchases' => 99000,
                'maximum_payment_time' => now()->addDays(2),
                'transaction_status' => 'done',
                'voucher_token' => 'Q9P9XDSNFML3SAZ',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
