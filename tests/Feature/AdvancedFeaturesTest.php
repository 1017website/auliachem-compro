<?php

namespace Tests\Feature;

use App\Models\ContentField;
use App\Models\Product;
use App\Models\User;
use App\Services\CompanyPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(CompanyPage::class)->import();
    }

    private function admin(bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    public function test_public_storage_files_are_served_through_the_storage_route(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/route-test.txt', 'storage route works');

        $this->get('/storage/cms/route-test.txt')
            ->assertOk()
            ->assertStreamedContent('storage route works');
    }

    public function test_product_catalog_is_managed_and_only_published_products_render(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/products', ['name_id' => 'Sodium Chloride', 'name_en' => 'Sodium Chloride', 'category' => 'industrial-salt', 'sku' => 'AC-001', 'sort_order' => 1, 'is_published' => 1, 'image' => UploadedFile::fake()->image('salt.jpg', 1200, 800)])->assertRedirect('/admin/products');
        $product = Product::firstOrFail();
        Storage::disk('public')->assertExists($product->image_path);
        $this->get('/?lang=en')->assertOk()->assertSee('Sodium Chloride')->assertSee('AC-001');
        $product->update(['is_published' => false]);
        $this->get('/')->assertDontSee('AC-001');
    }

    public function test_technical_seo_endpoints_and_metadata_render(): void
    {
        $this->get('/')->assertOk()->assertSee('rel="canonical"', false)->assertSee('application/ld+json', false)->assertSee('hreflang="zh-CN"', false);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('?lang=en', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertSee('Disallow: /admin');
    }

    public function test_inactive_admin_is_denied_and_security_pages_work(): void
    {
        $inactive = $this->admin(false);
        $this->actingAs($inactive)->get('/admin')->assertForbidden();
        $active = $this->admin();
        $this->actingAs($active)->get('/admin/profile')->assertOk()->assertSee('Riwayat login');
        $this->post('/admin/profile/two-factor')->assertRedirect();
        $this->assertNotNull(session('two_factor_setup'));
    }

    public function test_conversions_are_counted_and_exported(): void
    {
        $this->post('/track', ['type' => 'email_click', 'locale' => 'en'])->assertNoContent();
        $this->assertDatabaseHas('conversion_events', ['type' => 'email_click', 'locale' => 'en']);
        $this->actingAs($this->admin())->get('/admin/analytics')->assertOk()->assertViewHas('conversionTotal', 1)->assertSee('Klik email');
        $this->get('/admin/analytics/export?days=30')->assertOk()->assertDownload();
    }

    public function test_media_metadata_can_be_saved(): void
    {
        Storage::fake('public');
        $field = ContentField::where('type', 'image')->where('key', '!=', 'brand_favicon')->firstOrFail();
        $section = $field->section;
        $payload = ['section' => $section, 'fields' => [], 'version' => [], 'alt' => [$field->id => ['id' => 'Foto fasilitas', 'en' => 'Facility photo', 'zh' => '设施照片']], 'image_position' => [$field->id => 'top'], 'mobile_uploads' => [$field->id => UploadedFile::fake()->image('mobile.jpg', 800, 1000)]];
        foreach (ContentField::where('section', $section)->get() as $item) {
            $payload['fields'][$item->id] = $item->values;
            $payload['version'][$item->id] = hash('sha256', json_encode($item->values));
        }
        $this->actingAs($this->admin())->put('/admin/content', $payload)->assertRedirect()->assertSessionHasNoErrors();
        $field->refresh();
        $this->assertSame('Facility photo', $field->alt_values['en']);
        $this->assertSame('top', $field->image_position);
        $this->assertStringStartsWith('/storage/cms/mobile/', $field->mobile_value);
    }
}
