<?php

namespace Database\Seeders;

use App\Models\MotorBrochure;
use App\Models\MotorBrochureItem;
use App\Models\MotorProduct;
use Illuminate\Database\Seeder;

class MotorAgustus2026Seeder extends Seeder
{
    public function run(): void
    {
        $brochure = MotorBrochure::updateOrCreate(
            ['period_label' => 'Agustus 2026'],
            [
                'source_name' => 'Brosur Agustus 2026',
                'status' => 'active',
                'notes' => implode("\n", [
                    'Syarat Pengajuan pada Brosur',
                    '1. KTP SUAMI & ISTRI, KK',
                    '2. BUKTI KERJA / SLIP GAJI / ID CARD',
                    '3. BUKTI PEMBAYARAN LISTRIK',
                    '',
                    'Catatan dari Brosur',
                    'DP Rumah Pribadi',
                    'DP Kendala BI Checking',
                    '15% DP Rumah Kontrak',
                ]),
            ]
        );

        $products = [
            ['beat-cbs', 'Beat CBS', 'Beat', 19562000],
            ['beat-street', 'Beat Street', 'Beat', 20884000],
            ['beat-deluxe-kunci', 'Beat Deluxe (Kunci)', 'Beat', 20884000],
            ['beat-deluxe-smartkey', 'Beat Deluxe (Smartkey)', 'Beat', 21414000],
            ['genio-cbs-bk', 'Genio CBS (BK)', 'Genio', 20682000],
            ['genio-cbs-pd-ph', 'Genio CBS (PD, PH)', 'Genio', 20882000],
            ['genio-cbs-iss', 'Genio CBS ISS', 'Genio', 21302000],
            ['scoopy-fashion', 'Scoopy Fashion', 'Scoopy', 24104000],
            ['scoopy-stylist-prestige-smartkey', 'Scoopy Stylist / Prestige (Smartkey)', 'Scoopy', 24909000],
            ['vario-125-cbs', 'Vario 125 CBS', 'Vario', 25582000],
            ['vario-125-cbs-iss', 'Vario 125 CBS ISS', 'Vario', 27295500],
            ['vario-125-street', 'Vario 125 Street', 'Vario', 27704000],
            ['vario-evo-160-cbs', 'Vario Evo 160 CBS', 'Vario', 28764000],
            ['vario-evo160-cbs-sp', 'Vario Evo160 CBS SP', 'Vario', 29014000],
            ['vario-evo-160-abs', 'Vario Evo 160 ABS', 'Vario', 32582000],
            ['stylo-160-cbs', 'Stylo 160 CBS', 'Stylo', 29982000],
            ['stylo-160-abs-se', 'Stylo 160 ABS SE', 'Stylo', 34468000],
            ['pcx-160-cbs', 'PCX 160 CBS', 'PCX', 35239000],
            ['pcx-160-abs', 'PCX 160 ABS', 'PCX', 40525000],
            ['pcx-160-abs-roadsync', 'PCX 160 ABS ROADSYNC', 'PCX', 43522000],
            ['adv-160-cbs', 'ADV 160 CBS', 'ADV', 38072000],
            ['adv-160-abs', 'ADV 160 ABS', 'ADV', 41807500],
            ['adv-160-abs-roadsync', 'ADV 160 ABS ROADSYNC', 'ADV', 43339500],
        ];

        foreach ($products as [$code, $name, $family, $cashPrice]) {
            $product = MotorProduct::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'family' => $family,
                    'status' => 'active',
                ]
            );

            MotorBrochureItem::updateOrCreate(
                [
                    'brochure_id' => $brochure->id,
                    'motor_product_id' => $product->id,
                ],
                ['cash_price' => $cashPrice]
            );
        }
    }
}
