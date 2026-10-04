<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\District;
use App\Models\Region;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_normalizes_phone_and_uses_postal_code_from_village(): void
    {
        [$region, $district, $village] = $this->createAddress();

        $response = $this->postJson('/customer/register', [
            'nama' => 'Ayu Customer',
            'telepon' => '+62 812-3456-7890',
            'wilayah' => $region->id,
            'kecamatan' => $district->id,
            'kelurahan' => $village->id,
            'kodePos' => '99999',
            'namaJalan' => 'Jalan Melati 1',
        ]);

        $response->assertRedirect(route('customer.show'))
            ->assertSessionHas('customer_id');

        $this->assertDatabaseHas('customers', [
            'full_name' => 'Ayu Customer',
            'phone' => '081234567890',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => '11160',
            'street_address' => 'Jalan Melati 1',
        ]);
        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_customer_registration',
            'customer_id' => Customer::query()->where('phone', '081234567890')->value('id'),
            'status' => 'queued',
        ]);

        $this->get('/customer')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/CustomerHome')
                ->where('customerName', 'Ayu Customer')
                ->where('customerPhone', '081234567890'));
    }

    public function test_customer_navigation_routes_render_separate_pages_with_profile_data(): void
    {
        $customer = $this->createCustomer('081234567890');
        $pages = [
            '/customer' => 'customer/CustomerHome',
            '/customer/motor' => 'customer/MotorPage',
            '/customer/dana-tunai' => 'customer/DanaTunaiPage',
            '/customer/simulasi' => 'customer/SimulationPage',
            '/customer/tentang' => 'customer/AboutPage',
            '/customer/chat' => 'customer/ChatPage',
        ];

        foreach ($pages as $url => $component) {
            $this->withSession(['customer_id' => $customer->id])
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where('customerName', $customer->full_name)
                    ->where('customerPhone', $customer->phone));
        }
    }

    public function test_every_customer_page_redirects_without_an_identified_session(): void
    {
        foreach ([
            '/customer',
            '/customer/motor',
            '/customer/dana-tunai',
            '/customer/simulasi',
            '/customer/tentang',
            '/customer/chat',
        ] as $url) {
            $this->get($url)->assertRedirect('/');
        }
    }

    public function test_existing_customer_is_identified_without_creating_another_record(): void
    {
        $customer = $this->createCustomer('081234567890');

        $this->postJson('/customer/identify', ['telepon' => '+62 812-3456-7890'])
            ->assertRedirect(route('customer.show'))
            ->assertSessionHas('customer_id', $customer->id);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_identify_returns_business_error_when_phone_is_not_registered(): void
    {
        $this->postJson('/customer/identify', ['telepon' => '081299999999'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['telepon'])
            ->assertJsonPath(
                'errors.telepon.0',
                'Nomor telepon belum terdaftar. Silakan pilih "Belum Pernah Pengajuan" untuk membuat data baru.'
            );

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_duplicate_registration_returns_the_existing_customer_message(): void
    {
        $customer = $this->createCustomer('081234567890');
        [$region, $district, $village] = $this->createAddress('32.71', '32.71.01', 'Bogor');

        $this->postJson('/customer/register', [
            'nama' => 'Customer Duplikat',
            'telepon' => '+62 812-3456-7890',
            'wilayah' => $region->id,
            'kecamatan' => $district->id,
            'kelurahan' => $village->id,
            'kodePos' => $village->postal_code,
            'namaJalan' => 'Jalan Kenanga',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['telepon'])
            ->assertJsonPath(
                'errors.telepon.0',
                'Nomor telepon sudah terdaftar. Silakan pilih "Sudah Pernah Pengajuan".'
            );

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_registration_rejects_mismatched_address_hierarchy(): void
    {
        [$region, , $village] = $this->createAddress();
        [$otherRegion, $otherDistrict] = $this->createAddress('32.71', '32.71.01', 'Bogor');

        $this->postJson('/customer/register', [
            'nama' => 'Ayu Customer',
            'telepon' => '081234567890',
            'wilayah' => $otherRegion->id,
            'kecamatan' => $otherDistrict->id,
            'kelurahan' => $village->id,
            'kodePos' => '11160',
            'namaJalan' => 'Jalan Melati 1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['wilayah'])
            ->assertJsonPath('errors.wilayah.0', 'Data wilayah yang dipilih tidak sesuai.');

        $this->assertDatabaseCount('customers', 0);
        $this->assertNotSame($region->id, $otherRegion->id);
    }

    public function test_customer_area_rejects_missing_or_stale_customer_session(): void
    {
        $this->get('/customer')->assertRedirect('/');

        $response = $this->withSession(['customer_id' => 999999])->get('/customer');

        $response->assertRedirect('/')
            ->assertSessionMissing('customer_id');
    }

    public function test_logout_invalidates_customer_session_and_is_post_only(): void
    {
        $customer = $this->createCustomer('081234567890');

        $response = $this->withSession([
            'customer_id' => $customer->id,
            'other_session_value' => 'discarded',
        ])->post('/customer/logout');

        $response->assertRedirect('/')
            ->assertSessionMissing('customer_id')
            ->assertSessionMissing('other_session_value');
        $this->get('/customer/logout')->assertStatus(405);
    }

    private function createAddress(
        string $regionCode = '31.73',
        string $districtCode = '31.73.03',
        string $districtName = 'Taman Sari'
    ): array {
        $region = Region::create([
            'code' => $regionCode,
            'name' => 'Kota Jakarta Barat',
            'type' => 'city',
        ]);
        $district = District::create([
            'region_id' => $region->id,
            'code' => $districtCode,
            'name' => $districtName,
        ]);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => $districtCode . '.1003',
            'name' => 'Maphar',
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);

        return [$region, $district, $village];
    }

    private function createCustomer(string $phone): Customer
    {
        [$region, $district, $village] = $this->createAddress();

        return Customer::create([
            'full_name' => 'Ayu Customer',
            'phone' => $phone,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => $village->postal_code,
            'street_address' => 'Jalan Melati 1',
        ]);
    }
}
