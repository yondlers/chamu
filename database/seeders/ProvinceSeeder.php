<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Seed South African provinces.
     */
    public function run(): void
    {
        $countryId = DB::table('countries')
            ->where('name', 'South Africa')
            ->value('id');

        if ($countryId === null) {
            return;
        }

        $provinces = [
            ['name' => 'Eastern Cape', 'code' => 'EC'],
            ['name' => 'Free State', 'code' => 'FS'],
            ['name' => 'Gauteng', 'code' => 'GP'],
            ['name' => 'KwaZulu-Natal', 'code' => 'KZN'],
            ['name' => 'Limpopo', 'code' => 'LP'],
            ['name' => 'Mpumalanga', 'code' => 'MP'],
            ['name' => 'Northern Cape', 'code' => 'NC'],
            ['name' => 'North West', 'code' => 'NW'],
            ['name' => 'Western Cape', 'code' => 'WC'],
        ];

        foreach ($provinces as $province) {
            DB::table('provinces')->updateOrInsert(
                [
                    'country_id' => $countryId,
                    'name' => $province['name'],
                ],
                [
                    'code' => $province['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
