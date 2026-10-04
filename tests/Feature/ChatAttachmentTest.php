<?php

namespace Tests\Feature;

use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\District;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use App\Models\WhatsAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\AttachmentFixtures;
use Tests\TestCase;

class ChatAttachmentTest extends TestCase
{
    use RefreshDatabase, AttachmentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('chat_private');
    }

    protected function tearDown(): void
    {
        $this->removeUploadFixtures();
        parent::tearDown();
    }

    public static function formats(): array
    {
        return array_map(fn ($extension) => [$extension], ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    /** @dataProvider formats */
    public function test_customer_can_upload_supported_formats_to_private_storage(string $extension): void
    {
        $conversation = $this->conversation();
        $file = $this->upload($extension);
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), ['attachments' => [$file]])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/customer/chat/' . $conversation->id);

        $attachment = ChatAttachment::firstOrFail();
        $this->assertNull($attachment->message->body);
        $this->assertSame($extension, $attachment->extension);
        $this->assertSame('fixture.' . $extension, $attachment->original_name);
        $this->assertSame('chat_private', $attachment->disk);
        $this->assertSame($file->getSize(), $attachment->size);
        $this->assertNotSame($attachment->original_name, $attachment->stored_name);
        $this->assertMatchesRegularExpression('/^[a-f0-9-]{36}\.' . $extension . '$/', $attachment->stored_name);
        $this->assertStringStartsWith('chat/' . $conversation->id . '/' . $attachment->message_id . '/', $attachment->path);
        Storage::disk('chat_private')->assertExists($attachment->path);
        $this->assertNotNull($conversation->fresh()->last_message_at);
        $this->assertFalse(Storage::disk('public')->exists($attachment->path));
    }

    public function test_text_and_images_share_one_message_and_polling_only_exposes_safe_metadata(): void
    {
        $conversation = $this->conversation('motor');
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), [
                'body' => 'Foto motor saya.', 'attachments' => [$this->upload('png'), $this->upload('pdf')],
                'sender_type' => 'admin', 'sender_id' => 999,
            ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseCount('chat_attachments', 2);
        $attachment = ChatAttachment::firstOrFail();
        $this->assertSame('customer', $attachment->message->sender_type);
        $this->get('/customer/chat/' . $conversation->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.0.body', 'Foto motor saya.')
            ->has('conversation.messages.0.attachments', 2)
            ->where('conversation.messages.0.attachments.0.mime_type', 'image/png')
            ->where('conversation.messages.0.attachments.0.attachment_type', 'image')
            ->where('conversation.messages.0.attachments.0.preview_url', '/customer/chat/attachments/' . $attachment->id . '?preview=1')
            ->missing('conversation.messages.0.attachments.0.path')
            ->missing('conversation.messages.0.attachments.0.disk')
            ->missing('conversation.messages.0.attachments.0.stored_name'));
        $this->get('/customer/chat/' . $conversation->id, [
            'X-Inertia' => 'true',
            'X-Inertia-Partial-Component' => 'customer/ConversationPage',
            'X-Inertia-Partial-Data' => 'conversation',
        ])->assertOk()->assertJsonPath('props.conversation.messages.0.attachments.0.id', $attachment->id);
    }

    public function test_owner_can_preview_image_download_documents_and_never_gets_a_public_url(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), ['attachments' => [$this->upload('png'), $this->upload('pdf')]]);
        $image = ChatAttachment::where('extension', 'png')->firstOrFail();
        $pdf = ChatAttachment::where('extension', 'pdf')->firstOrFail();
        $imageResponse = $this->get('/customer/chat/attachments/' . $image->id . '?preview=1')
            ->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline;', $imageResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $imageResponse->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('chat_private')->get($image->path), $imageResponse->streamedContent());
        $this->get('/customer/chat/attachments/' . $image->id)->assertOk()->assertDownload('fixture.png');
        $this->get('/customer/chat/attachments/' . $pdf->id . '?preview=1')
            ->assertOk()->assertDownload('fixture.pdf')->assertHeader('Content-Type', 'application/pdf');
        $this->get('/storage/' . $image->path)->assertNotFound();
    }

    public function test_attachment_ownership_and_admin_authorization_are_independent(): void
    {
        $owner = $this->conversation();
        $other = $this->conversation();
        $this->withSession(['customer_id' => $owner->customer_id])
            ->post($this->messageUrl($owner), ['attachments' => [$this->upload('pdf')]]);
        $attachment = ChatAttachment::firstOrFail();
        $this->withSession(['customer_id' => $other->customer_id])
            ->get('/customer/chat/attachments/' . $attachment->id)->assertNotFound();
        $this->post($this->messageUrl($owner), ['attachments' => [$this->upload('png')]])->assertNotFound();
        $this->get('/admin/chat/attachments/' . $attachment->id)->assertRedirect('/admin/login');
        $this->withSession(['customer_id' => null])
            ->get('/customer/chat/attachments/' . $attachment->id)->assertRedirect('/');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin->fresh())->get('/admin/chat/attachments/' . $attachment->id)->assertOk()->assertDownload('fixture.pdf');
        $staff = User::factory()->create(['is_admin' => false, 'role' => 'staff', 'is_active' => true]);
        $this->actingAs($staff->fresh())->get('/admin/chat/attachments/' . $attachment->id)->assertOk();
        $inactive = User::factory()->create(['is_admin' => false, 'role' => 'staff', 'is_active' => false]);
        $this->actingAs($inactive->fresh())->get('/admin/chat/attachments/' . $attachment->id)->assertForbidden();
        $this->post($this->messageUrl($owner, 'admin'), ['attachments' => [$this->upload('pdf')]])->assertForbidden();
        $this->assertDatabaseCount('chat_attachments', 1);
    }

    public function test_admin_can_send_image_and_document_and_customer_reads_them(): void
    {
        $conversation = $this->conversation();
        $this->actingAs(User::factory()->create(['is_admin' => true])->fresh())
            ->post($this->messageUrl($conversation, 'admin'), [
                'body' => 'Informasi untuk Anda.',
                'attachments' => [$this->upload('png'), $this->upload('pdf')],
            ])->assertSessionHasNoErrors()->assertRedirect('/admin/chat/' . $conversation->id);
        $attachment = ChatAttachment::firstOrFail();
        $this->assertSame('admin', $attachment->message->sender_type);
        $this->assertNull($attachment->message->read_at);
        $this->get('/admin/chat/' . $conversation->id)->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.0.attachments.0.download_url', '/admin/chat/attachments/' . $attachment->id));
        Auth::logout();
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->get('/customer/chat')->assertInertia(fn (Assert $page) => $page->where('conversations.0.unreadCount', 1));
        $this->get('/customer/chat/' . $conversation->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('conversation.messages.0.attachments', 2));
        $this->assertNotNull($attachment->message->fresh()->read_at);
        $this->get('/customer/chat/attachments/' . $attachment->id . '?preview=1')->assertOk();
    }

    public static function unsafeFiles(): array
    {
        return array_map(fn ($extension) => [$extension], [
            'exe', 'bat', 'cmd', 'js', 'php', 'sh', 'dll', 'com', 'scr', 'msi', 'svg', 'html',
        ]);
    }

    /** @dataProvider unsafeFiles */
    public function test_executable_and_active_formats_are_rejected(string $extension): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), ['attachments' => [$this->upload($extension)]])
            ->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('chat_messages', 0);
        $this->assertSame([], Storage::disk('chat_private')->allFiles());
    }

    public function test_renamed_script_and_mismatched_mime_are_rejected(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id]);
        foreach (['jpg', 'pdf', 'doc', 'docx', 'xls', 'xlsx'] as $extension) {
            $this->post($this->messageUrl($conversation), [
                'attachments' => [$this->upload($extension, '<?php echo "not a document";')],
            ])->assertSessionHasErrors('attachments.0');
        }
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_image_and_document_size_limits_and_maximum_file_count_are_enforced(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id]);
        $this->post($this->messageUrl($conversation), ['attachments' => [
            $this->upload('png', str_repeat('x', 5 * 1024 * 1024 + 1)),
        ]])->assertSessionHasErrors(['attachments.0' => 'Ukuran foto maksimal 5 MB per file.']);
        $this->post($this->messageUrl($conversation), ['attachments' => [
            $this->upload('pdf', str_repeat('x', 10 * 1024 * 1024 + 1)),
        ]])->assertSessionHasErrors(['attachments.0' => 'Ukuran dokumen maksimal 10 MB per file.']);
        $files = array_map(fn () => $this->upload('pdf'), range(1, 6));
        $this->post($this->messageUrl($conversation), ['attachments' => $files])->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('chat_messages', 0);
        $this->post($this->messageUrl($conversation), ['attachments' => array_slice($files, 0, 5)])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('chat_attachments', 5);
    }

    public function test_empty_message_empty_file_and_closed_conversation_are_rejected(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id]);
        $this->post($this->messageUrl($conversation), ['body' => '  '])->assertSessionHasErrors('body');
        $this->post($this->messageUrl($conversation), ['attachments' => [$this->upload('pdf', '')]])
            ->assertSessionHasErrors('attachments.0');
        $conversation->update(['status' => 'closed']);
        $this->post($this->messageUrl($conversation), ['attachments' => [$this->upload('pdf')]])->assertNotFound();
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_database_failure_rolls_back_message_attachments_timestamp_and_files(): void
    {
        $conversation = $this->conversation();
        $created = 0;
        ChatAttachment::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new \RuntimeException('Simulated metadata failure');
            }
        });
        try {
            $this->withSession(['customer_id' => $conversation->customer_id])
                ->post($this->messageUrl($conversation), ['attachments' => [$this->upload('png'), $this->upload('pdf')]])
                ->assertSessionHasErrors('attachments');
            $this->assertDatabaseCount('chat_messages', 0);
            $this->assertDatabaseCount('chat_attachments', 0);
            $this->assertNull($conversation->fresh()->last_message_at);
            $this->assertSame([], Storage::disk('chat_private')->allFiles());
        } finally {
            ChatAttachment::flushEventListeners();
        }
    }

    public function test_storage_failure_rolls_back_database_and_cleans_an_earlier_file(): void
    {
        $conversation = $this->conversation();
        $disk = Storage::disk('chat_private');
        $mock = \Mockery::mock($disk)->makePartial();
        $writes = 0;
        $mock->shouldReceive('putFileAs')->andReturnUsing(function (...$args) use ($disk, &$writes) {
            if (++$writes === 2) {
                throw new \RuntimeException('Simulated full disk');
            }
            return $disk->putFileAs(...$args);
        });
        Storage::set('chat_private', $mock);
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), ['attachments' => [$this->upload('png'), $this->upload('pdf')]])
            ->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('chat_messages', 0);
        $this->assertDatabaseCount('chat_attachments', 0);
        $this->assertSame([], $disk->allFiles());
    }

    public function test_reminder_contains_only_internal_notification_and_no_customer_content(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), [
                'body' => 'Data pribadi tidak untuk reminder',
                'attachments' => [$this->upload('pdf', null, 'dokumen-rahasia.pdf')],
            ])->assertSessionHasNoErrors();
        $message = $conversation->messages()->firstOrFail();
        $this->assertDatabaseCount('whatsapp_notifications', 2);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_chat_message',
            'reference_type' => 'chat_message',
            'reference_id' => (string) $message->id,
        ]);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_attachment',
            'reference_type' => 'chat_message',
            'reference_id' => (string) $message->id,
        ]);
        $storedReminderData = json_encode(WhatsAppNotification::all()->toArray());
        $this->assertStringNotContainsString('Data pribadi tidak untuk reminder', $storedReminderData);
        $this->assertStringNotContainsString('dokumen-rahasia.pdf', $storedReminderData);
    }

    public function test_display_filename_is_sanitized_and_missing_storage_returns_safe_not_found(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), ['attachments' => [$this->upload('pdf', null, '<script>alert(1)</script>.pdf')]]);
        $attachment = ChatAttachment::firstOrFail();
        $this->assertStringNotContainsString('<', $attachment->original_name);
        $this->assertStringNotContainsString('/', $attachment->original_name);
        Storage::disk('chat_private')->delete($attachment->path);
        $this->get('/customer/chat/attachments/' . $attachment->id)->assertNotFound()
            ->assertDontSee($attachment->path);
    }

    public function test_delete_message_and_conversation_clean_private_files(): void
    {
        $conversation = $this->conversation();
        $this->withSession(['customer_id' => $conversation->customer_id])
            ->post($this->messageUrl($conversation), ['attachments' => [$this->upload('png')]]);
        $message = \App\Models\ChatMessage::firstOrFail();
        $path = $message->attachments()->firstOrFail()->path;
        $message->delete();
        Storage::disk('chat_private')->assertMissing($path);

        $this->post($this->messageUrl($conversation), ['attachments' => [$this->upload('pdf')]]);
        $secondPath = ChatAttachment::firstOrFail()->path;
        $conversation->delete();
        Storage::disk('chat_private')->assertMissing($secondPath);
        $this->assertDatabaseMissing('chat_attachments', ['path' => $secondPath]);
    }

    public function test_oversized_request_without_parseable_files_gets_a_safe_limit_error(): void
    {
        $request = \Illuminate\Http\Request::create('/customer/chat/1/messages', 'POST', [], [], [], [
            'CONTENT_LENGTH' => (string) (50 * 1024 * 1024),
        ]);
        try {
            app(\App\Services\ChatMessageValidator::class)->validate($request);
            $this->fail('Expected the oversized request to be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('attachments', $exception->errors());
            $this->assertSame(
                'Ukuran total unggahan terlalu besar. Kurangi jumlah atau ukuran file, lalu coba lagi.',
                $exception->errors()['attachments'][0]
            );
        }
    }

    private function messageUrl(ChatConversation $conversation, string $audience = 'customer'): string
    {
        return '/' . $audience . '/chat/' . $conversation->id . '/messages';
    }

    private function conversation(string $context = 'dana_tunai'): ChatConversation
    {
        $number = Customer::count() + 1;
        $region = Region::firstOrCreate(['code' => 'attachment-test'], ['name' => 'Test', 'type' => 'city']);
        $district = District::firstOrCreate(['code' => 'attachment-test'], ['region_id' => $region->id, 'name' => 'Test']);
        $village = Village::firstOrCreate(['code' => 'attachment-test'], [
            'district_id' => $district->id, 'name' => 'Test', 'type' => 'urban_village', 'postal_code' => '11160',
        ]);
        $customer = Customer::create([
            'full_name' => 'Attachment Test ' . $number, 'phone' => '08123456000' . $number,
            'region_id' => $region->id, 'district_id' => $district->id, 'village_id' => $village->id,
            'postal_code' => '11160', 'street_address' => 'Jalan Test',
        ]);
        $motor = $context === 'motor' ? MotorProduct::create([
            'code' => 'ATTACHMENT-MOTOR', 'name' => 'Vario Test', 'family' => 'Vario', 'status' => 'active',
        ]) : null;
        return ChatConversation::create([
            'customer_id' => $customer->id, 'context_type' => $context,
            'motor_product_id' => $motor?->id, 'status' => 'open',
        ]);
    }
}
