<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\AdminActivityLog;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\DanaTunaiApplication;
use App\Models\District;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\ReportExport;
use App\Models\User;
use App\Models\Village;
use App\Services\AdminReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_require_admin_capability_and_customer_session_is_not_admin_auth(): void
    {
        $customer = $this->customer();
        $this->withSession(['customer_id' => $customer->id])
            ->get('/admin/reports')->assertRedirect('/admin/login');
        $this->withSession(['customer_id' => $customer->id])
            ->get('/admin/reports/export?report_type=customers')->assertRedirect('/admin/login');

        $this->actingAs($this->user('staff'))
            ->get('/admin/reports')->assertForbidden();
        $this->actingAs($this->user('staff'))
            ->get('/admin/reports/export?report_type=customers')->assertForbidden();

        $this->actingAs($this->user('admin'))
            ->get('/admin/reports')->assertOk();
    }

    public function test_all_report_types_render_preview_from_database_queries(): void
    {
        $this->reportFixtures();
        $admin = $this->user('admin');

        foreach (array_keys(app(AdminReportService::class)->reportTypes()) as $type) {
            $this->actingAs($admin)->get('/admin/reports?report_type=' . $type)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('admin/reports/IndexPage')
                    ->where('filters.report_type', $type)
                    ->has('columns')
                    ->has('results.data'));
        }
    }

    public function test_each_report_type_can_be_downloaded_as_an_audited_xlsx(): void
    {
        $this->reportFixtures();
        $admin = $this->user('admin');
        $filenames = [
            'customers' => 'AFH_Customers_Report_all_all.xlsx',
            'motor_interest' => 'AFH_MotorInterest_Report_all_all.xlsx',
            'credit_applications' => 'AFH_CreditApplications_Report_all_all.xlsx',
            'dana_tunai' => 'AFH_DanaTunai_Report_all_all.xlsx',
            'chat_activity' => 'AFH_ChatActivity_Report_all_all.xlsx',
            'documents' => 'AFH_Documents_Report_all_all.xlsx',
            'customer_growth' => 'AFH_CustomerGrowth_Report_all_all.xlsx',
            'application_status' => 'AFH_ApplicationStatus_Report_all_all.xlsx',
            'motor_interest_summary' => 'AFH_MotorInterestSummary_Report_all_all.xlsx',
        ];

        foreach ($filenames as $type => $filename) {
            $this->actingAs($admin)
                ->get('/admin/reports/export?report_type=' . $type)
                ->assertOk()
                ->assertDownload($filename);
        }

        $this->assertDatabaseCount('report_exports', count($filenames));
        $this->assertSame(
            count($filenames),
            AdminActivityLog::query()->where('action', 'report.exported')->count()
        );
    }

    public function test_credit_application_export_uses_preview_filters_and_logs_xlsx_metadata(): void
    {
        [$customer, $region, $motor, $application] = $this->reportFixtures();
        $otherApplication = CreditApplication::create([
            'application_number' => 'AFH-CR-EXCLUDED',
            'customer_id' => $customer->id,
            'motor_product_id' => $motor->id,
            'cash_price' => '27000000.00',
            'status' => 'submitted',
            'source' => 'referral',
            'submitted_at' => now(),
        ]);
        $admin = $this->user('admin');
        $query = http_build_query([
            'report_type' => 'credit_applications',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'region_id' => $region->id,
            'motor_id' => $motor->id,
            'status' => 'submitted',
            'source' => 'website',
        ]);

        $this->actingAs($admin)->get('/admin/reports?' . $query)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('results.total', 1)
                ->where('results.data.0.application_number', $application->application_number));

        $response = $this->actingAs($admin)->get('/admin/reports/export?' . $query)
            ->assertOk()
            ->assertDownload('AFH_CreditApplications_Report_' . now()->toDateString() . '_' . now()->toDateString() . '.xlsx');

        $exportRecord = ReportExport::query()->firstOrFail();
        $this->assertSame((int) $admin->id, (int) $exportRecord->admin_id);
        $this->assertSame('credit_applications', $exportRecord->report_type);
        $this->assertSame(1, $exportRecord->row_count);
        $this->assertSame([
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'region_id' => (string) $region->id,
            'motor_id' => (string) $motor->id,
            'status' => 'submitted',
            'source' => 'website',
        ], $exportRecord->filters);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'report.exported',
            'entity_type' => 'report_export',
            'entity_id' => (string) $exportRecord->id,
        ]);

        $path = $response->baseResponse->getFile()->getPathname();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheetText = $this->spreadsheetText($spreadsheet->getActiveSheet()->toArray());
        $this->assertStringContainsString($application->application_number, $sheetText);
        $this->assertStringNotContainsString($otherApplication->application_number, $sheetText);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_document_export_contains_metadata_but_not_private_storage_path(): void
    {
        [$customer, , , , $document] = $this->reportFixtures();
        $response = $this->actingAs($this->user('admin'))
            ->get('/admin/reports/export?report_type=documents')
            ->assertOk()->assertDownload('AFH_Documents_Report_all_all.xlsx');

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheetText = $this->spreadsheetText($spreadsheet->getActiveSheet()->toArray());
        $this->assertStringContainsString($customer->full_name, $sheetText);
        $this->assertStringContainsString($document->original_name, $sheetText);
        $this->assertStringNotContainsString('private/secret-customer-document.pdf', $sheetText);
        $this->assertStringNotContainsString('isi pesan rahasia', $sheetText);
        $spreadsheet->disconnectWorksheets();
    }

    private function reportFixtures(): array
    {
        $region = Region::create(['code' => 'report-region', 'name' => 'Jakarta Report', 'type' => 'city']);
        $district = District::create(['region_id' => $region->id, 'code' => 'report-district', 'name' => 'Kecamatan Report']);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => 'report-village',
            'name' => 'Kelurahan Report',
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);
        $customer = Customer::create([
            'full_name' => 'Customer Report Test',
            'phone' => '081234567890',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => '11160',
            'street_address' => 'Jalan Report',
        ]);
        $motor = MotorProduct::create([
            'code' => 'report-motor',
            'name' => 'Motor Report Test',
            'family' => 'Test',
            'status' => 'active',
        ]);
        $application = CreditApplication::create([
            'application_number' => 'AFH-CR-REPORT',
            'customer_id' => $customer->id,
            'motor_product_id' => $motor->id,
            'cash_price' => '27000000.00',
            'down_payment' => '5000000.00',
            'tenor_months' => 36,
            'installment_amount' => '1000000.00',
            'installment_source' => 'Brosur',
            'status' => 'submitted',
            'source' => 'website',
            'submitted_at' => now(),
        ]);
        $danaConversation = ChatConversation::create([
            'customer_id' => $customer->id,
            'context_type' => 'dana_tunai',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        DanaTunaiApplication::create([
            'application_number' => 'AFH-DT-REPORT',
            'customer_id' => $customer->id,
            'chat_conversation_id' => $danaConversation->id,
            'status' => 'submitted',
            'source' => 'website',
            'submitted_at' => now(),
        ]);
        $motorConversation = ChatConversation::create([
            'customer_id' => $customer->id,
            'context_type' => 'motor',
            'motor_product_id' => $motor->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $message = ChatMessage::create([
            'chat_conversation_id' => $motorConversation->id,
            'sender_type' => 'customer',
            'sender_id' => $customer->id,
            'body' => 'isi pesan rahasia',
        ]);
        $message->attachments()->create([
            'original_name' => 'attachment-private.pdf',
            'stored_name' => 'secret.pdf',
            'path' => 'private/secret-chat-file.pdf',
            'disk' => 'chat_private',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 128,
            'attachment_type' => 'document',
        ]);
        $document = CustomerDocument::create([
            'customer_id' => $customer->id,
            'credit_application_id' => $application->id,
            'document_type' => 'identity',
            'original_name' => 'identity-report.pdf',
            'disk' => 'chat_private',
            'path' => 'private/secret-customer-document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 256,
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        return [$customer, $region, $motor, $application, $document];
    }

    private function customer(): Customer
    {
        [$customer] = $this->reportFixtures();

        return $customer;
    }

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => 'Report ' . ucfirst($role),
            'email' => 'report-' . $role . '-' . User::count() . '@example.test',
            'password' => Hash::make('report-test-password'),
            'is_admin' => false,
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function spreadsheetText(array $rows): string
    {
        return implode(' ', array_map(
            fn (array $row): string => implode(' ', array_map(fn ($value): string => (string) $value, $row)),
            $rows
        ));
    }
}
