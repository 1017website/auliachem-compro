<?php

namespace Tests\Feature;

use App\Mail\NewQuoteRequest;
use App\Models\Document;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\CompanyPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(CompanyPage::class)->import();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    public function test_public_quote_form_saves_lead_and_sends_notification(): void
    {
        Mail::fake();
        $this->get('/?lang=id')->assertOk()->assertSee('Ceritakan kebutuhan Anda.');
        $response = $this->post('/quote-requests', [
            'name' => 'Budi Santoso', 'company' => 'PT Contoh', 'email' => 'budi@example.com',
            'phone' => '+62 812 3456', 'interest' => 'industrial-salt', 'quantity' => '5 ton',
            'message' => 'Mohon penawaran beserta CoA untuk kebutuhan produksi.', 'locale' => 'id', 'website' => '',
        ]);
        $response->assertRedirect(route('home', ['lang' => 'id']).'#quote-form')->assertSessionHas('quote_status');
        $this->assertDatabaseHas('quote_requests', ['email' => 'budi@example.com', 'status' => 'new']);
        Mail::assertSent(NewQuoteRequest::class, fn ($mail) => $mail->quote->email === 'budi@example.com');
    }

    public function test_quote_form_rejects_invalid_data_and_silently_drops_honeypot(): void
    {
        $this->post('/quote-requests', ['locale' => 'id'])->assertSessionHasErrors(['name', 'email', 'interest', 'message']);
        $this->post('/quote-requests', ['locale' => 'id', 'website' => 'spam.example'])->assertRedirect();
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_admin_can_manage_quote_status(): void
    {
        $quote = QuoteRequest::create([
            'name' => 'Client', 'email' => 'client@example.com', 'interest' => 'chemical',
            'message' => 'Need chemical quotation.', 'locale' => 'en',
        ]);
        $this->get('/admin/quote-requests')->assertRedirect('/admin/login');
        $this->actingAs($this->admin())->get('/admin/quote-requests')->assertOk()->assertSee('client@example.com');
        $this->patch('/admin/quote-requests/'.$quote->id, ['status' => 'contacted'])->assertRedirect();
        $this->assertSame('contacted', $quote->fresh()->status);
        $this->assertNotNull($quote->fresh()->contacted_at);
    }

    public function test_admin_uploads_private_pdf_and_public_can_download_published_document(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin())->post('/admin/documents', [
            'title' => 'Auliachem TDS Sodium Chloride 2026', 'category' => 'tds', 'description' => 'Product specification',
            'is_published' => '1', 'file' => UploadedFile::fake()->create('tds.pdf', 80, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
        $this->get('/?lang=en')->assertOk()->assertSee('Auliachem TDS Sodium Chloride 2026')->assertSee(route('documents.download', $document), false);
        $this->get('/documents/'.$document->id.'/download')->assertOk()->assertDownload('tds.pdf');
        $this->assertSame(1, $document->fresh()->downloads);
        $document->update(['is_published' => false]);
        $this->get('/documents/'.$document->id.'/download')->assertNotFound();
        $this->get('/')->assertDontSee('Auliachem TDS Sodium Chloride 2026');
    }

    public function test_document_upload_rejects_non_pdf(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin())->post('/admin/documents', [
            'title' => 'Unsafe file', 'category' => 'other', 'is_published' => '1',
            'file' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 0);
    }
}
