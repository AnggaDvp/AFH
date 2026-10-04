<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Region;
use App\Models\Village;
use Database\Seeders\JabodetabekRegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_regions_endpoint_returns_jabodetabek_regions(): void
    {
        $region = Region::create([
            'code' => '31.73',
            'name' => 'Kota Administrasi Jakarta Barat',
            'type' => 'city',
        ]);

        $this->getJson('/api/regions')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $region->id,
                'code' => '31.73',
                'name' => 'Kota Administrasi Jakarta Barat',
            ]);
    }

    public function test_region_endpoint_returns_only_its_districts(): void
    {
        $region = Region::create([
            'code' => '31.73',
            'name' => 'Kota Administrasi Jakarta Barat',
            'type' => 'city',
        ]);
        $district = District::create([
            'region_id' => $region->id,
            'code' => '31.73.03',
            'name' => 'Taman Sari',
        ]);

        $this->getJson("/api/regions/{$region->id}/districts")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $district->id,
                'region_id' => $region->id,
                'code' => '31.73.03',
            ]);
    }

    public function test_district_endpoint_returns_its_villages_and_postal_codes(): void
    {
        $region = Region::create([
            'code' => '31.73',
            'name' => 'Kota Administrasi Jakarta Barat',
            'type' => 'city',
        ]);
        $district = District::create([
            'region_id' => $region->id,
            'code' => '31.73.03',
            'name' => 'Taman Sari',
        ]);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => '31.73.03.1003',
            'name' => 'Maphar',
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);

        $this->getJson("/api/districts/{$district->id}/villages")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $village->id,
                'code' => '31.73.03.1003',
                'type' => 'urban_village',
                'postal_code' => '11160',
            ]);

        $this->assertSame('11160', $village->fresh()->postal_code);
    }

    public function test_nested_village_endpoint_rejects_a_district_from_another_region(): void
    {
        $region = Region::create([
            'code' => '31.73',
            'name' => 'Kota Administrasi Jakarta Barat',
            'type' => 'city',
        ]);
        $otherRegion = Region::create([
            'code' => '31.74',
            'name' => 'Kota Administrasi Jakarta Selatan',
            'type' => 'city',
        ]);
        $district = District::create([
            'region_id' => $otherRegion->id,
            'code' => '31.74.01',
            'name' => 'Kebayoran Baru',
        ]);

        $this->getJson("/api/regions/{$region->id}/districts/{$district->id}/villages")
            ->assertNotFound();
    }

    public function test_jabodetabek_seeder_is_idempotent_and_contains_the_expected_hierarchy(): void
    {
        $this->seed(JabodetabekRegionSeeder::class);
        $this->seed(JabodetabekRegionSeeder::class);

        $this->assertDatabaseCount('regions', 13);
        $this->assertDatabaseCount('districts', 183);
        $this->assertDatabaseCount('villages', 1502);
        $this->assertDatabaseHas('villages', [
            'code' => '31.73.03.1003',
            'name' => 'Maphar',
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);
        $this->assertDatabaseHas('villages', [
            'code' => '32.16.01.1007',
            'name' => 'Setia Asih',
            'type' => 'urban_village',
            'postal_code' => '17215',
        ]);
    }
}
