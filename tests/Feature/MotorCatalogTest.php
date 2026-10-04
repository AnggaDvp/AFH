<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\District;
use App\Models\MotorBrochure;
use App\Models\MotorBrochureItem;
use App\Models\MotorFinancingOption;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\Village;
use Database\Seeders\MotorAgustus2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MotorCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_motor_seeder_is_idempotent_and_creates_the_brochure_catalog(): void
    {
        $this->seed(MotorAgustus2026Seeder::class);
        $this->seed(MotorAgustus2026Seeder::class);

        $this->assertDatabaseCount('motor_products', 23);
        $this->assertDatabaseCount('motor_brochures', 1);
        $this->assertDatabaseCount('motor_brochure_items', 23);
        $this->assertDatabaseCount('motor_financing_options', 0);
        $this->assertDatabaseHas('motor_brochures', [
            'period_label' => 'Agustus 2026',
            'source_name' => 'Brosur Agustus 2026',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('motor_brochure_items', [
            'cash_price' => 19562000,
        ]);
        $actualPrices = MotorBrochureItem::query()
            ->join('motor_products', 'motor_products.id', '=', 'motor_brochure_items.motor_product_id')
            ->orderBy('motor_products.code')
            ->pluck('cash_price', 'motor_products.code')
            ->all();
        $this->assertSame([
            'adv-160-abs' => 41807500,
            'adv-160-abs-roadsync' => 43339500,
            'adv-160-cbs' => 38072000,
            'beat-cbs' => 19562000,
            'beat-deluxe-kunci' => 20884000,
            'beat-deluxe-smartkey' => 21414000,
            'beat-street' => 20884000,
            'genio-cbs-bk' => 20682000,
            'genio-cbs-iss' => 21302000,
            'genio-cbs-pd-ph' => 20882000,
            'pcx-160-abs' => 40525000,
            'pcx-160-abs-roadsync' => 43522000,
            'pcx-160-cbs' => 35239000,
            'scoopy-fashion' => 24104000,
            'scoopy-stylist-prestige-smartkey' => 24909000,
            'stylo-160-abs-se' => 34468000,
            'stylo-160-cbs' => 29982000,
            'vario-125-cbs' => 25582000,
            'vario-125-cbs-iss' => 27295500,
            'vario-125-street' => 27704000,
            'vario-evo-160-abs' => 32582000,
            'vario-evo-160-cbs' => 28764000,
            'vario-evo160-cbs-sp' => 29014000,
        ], $actualPrices);
    }

    public function test_customer_motor_catalog_uses_active_database_brochure_and_filters(): void
    {
        $this->seed(MotorAgustus2026Seeder::class);
        $customer = $this->createCustomer();
        $inactiveBrochure = MotorBrochure::create([
            'period_label' => 'Juli 2026',
            'source_name' => 'Brosur Juli 2026',
            'status' => 'archived',
        ]);
        $inactiveProduct = MotorProduct::create([
            'code' => 'archived-test-motor',
            'name' => 'Motor Arsip',
            'family' => 'Beat',
            'status' => 'active',
        ]);
        MotorBrochureItem::create([
            'brochure_id' => $inactiveBrochure->id,
            'motor_product_id' => $inactiveProduct->id,
            'cash_price' => 12345678,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get('/customer/motor?search=Beat&family=Beat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/MotorPage')
                ->where('brochure.periodLabel', 'Agustus 2026')
                ->where('search', 'Beat')
                ->where('selectedFamily', 'Beat')
                ->has('motors', 4)
                ->where('motors.0.name', 'Beat CBS')
                ->where('motors.0.cashPrice', 19562000));
    }

    public function test_customer_motor_api_returns_only_filtered_listing_fields(): void
    {
        $this->seed(MotorAgustus2026Seeder::class);
        $customer = $this->createCustomer();

        $this->withSession(['customer_id' => $customer->id])
            ->getJson('/api/customer/motors?search=PCX')
            ->assertOk()
            ->assertJsonCount(3);
        $this->withSession(['customer_id' => $customer->id])
            ->getJson('/api/customer/motors?family=Beat')
            ->assertOk()
            ->assertJsonCount(4);

        $response = $this->withSession(['customer_id' => $customer->id])
            ->getJson('/api/customer/motors?search=PCX&family=PCX')
            ->assertOk();
        $motors = $response->json();

        $this->assertCount(3, $motors);
        $this->assertSame(
            ['id', 'name', 'family', 'cash_price', 'brochure_period'],
            array_keys($motors[0])
        );
        $this->assertSame('PCX 160 CBS', $motors[0]['name']);
        $this->assertSame(35239000, $motors[0]['cash_price']);
        $this->assertSame('Agustus 2026', $motors[0]['brochure_period']);
    }

    public function test_motor_detail_reads_cash_price_and_financing_values_from_database(): void
    {
        $this->seed(MotorAgustus2026Seeder::class);
        $customer = $this->createCustomer();
        $product = MotorProduct::where('code', 'beat-cbs')->firstOrFail();
        $item = MotorBrochureItem::where('motor_product_id', $product->id)->firstOrFail();

        MotorFinancingOption::create([
            'brochure_item_id' => $item->id,
            'down_payment_source_value' => '3500',
            'tenor_code' => '11',
            'tenor_label' => '11',
            'installment_source_value' => '1543',
            'is_available' => true,
        ]);
        MotorFinancingOption::create([
            'brochure_item_id' => $item->id,
            'down_payment_source_value' => '3500',
            'tenor_code' => '36_32',
            'tenor_label' => '36 = 32',
            'installment_source_value' => null,
            'is_available' => false,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get('/customer/motor/beat-cbs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/MotorDetailPage')
                ->where('motor.name', 'Beat CBS')
                ->where('motor.cashPrice', 19562000)
                ->where('brochure.sourceName', 'Brosur Agustus 2026')
                ->has('financingOptions', 2)
                ->where('financingOptions.0.installmentValue', '1543')
                ->where('financingOptions.1.isAvailable', false)
                ->where('financingOptions.1.installmentValue', null));

        $this->withSession(['customer_id' => $customer->id])
            ->getJson('/api/customer/motors/beat-cbs')
            ->assertOk()
            ->assertJsonPath('product.cash_price', 19562000)
            ->assertJsonPath('brochure.period_label', 'Agustus 2026')
            ->assertJsonPath('financing_options.0.installment_source_value', '1543')
            ->assertJsonPath('financing_options.1.is_available', false)
            ->assertJsonPath('financing_options.1.installment_source_value', null);
    }

    public function test_motor_listing_and_detail_require_an_identified_customer(): void
    {
        $this->seed(MotorAgustus2026Seeder::class);

        $this->get('/customer/motor')->assertRedirect('/');
        $this->get('/customer/motor/beat-cbs')->assertRedirect('/');
        $this->get('/api/customer/motors')->assertRedirect('/');
        $this->get('/api/customer/motors/beat-cbs')->assertRedirect('/');
    }

    private function createCustomer(): Customer
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

        return Customer::create([
            'full_name' => 'Ayu Customer',
            'phone' => '081234567890',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => $village->postal_code,
            'street_address' => 'Jalan Melati 1',
        ]);
    }
}
