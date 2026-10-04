<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Region;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JabodetabekRegionSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/jabodetabek.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($data): void {
            $regionsByCode = [];

            foreach ($data['regions'] as $row) {
                $regionsByCode[$row['code']] = Region::updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'type' => $row['type'],
                    ]
                );
            }

            $districtsByCode = [];

            foreach ($data['districts'] as $row) {
                $region = $regionsByCode[$row['region_code']] ?? null;
                if ($region === null) {
                    throw new RuntimeException("Unknown parent region code: {$row['region_code']}");
                }

                $districtsByCode[$row['code']] = District::updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'region_id' => $region->id,
                        'name' => $row['name'],
                    ]
                );
            }

            foreach ($data['villages'] as $row) {
                $district = $districtsByCode[$row['district_code']] ?? null;
                if ($district === null) {
                    throw new RuntimeException("Unknown parent district code: {$row['district_code']}");
                }

                Village::updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'district_id' => $district->id,
                        'name' => $row['name'],
                        'type' => $row['type'],
                        'postal_code' => $row['postal_code'],
                    ]
                );
            }
        });
    }
}
