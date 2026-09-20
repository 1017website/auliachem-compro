<?php

namespace Tests\Feature;

use App\Models\ContentField;
use App\Models\User;
use App\Services\CompanyPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(CompanyPage::class)->import();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    private function payload(string $section): array
    {
        $fields = $section === 'media' ? ContentField::where('type', 'image')->get() : ContentField::where('section', $section)->get();

        return [
            'section' => $section,
            'fields' => $fields->mapWithKeys(fn ($field) => [$field->id => $field->values])->all(),
            'version' => $fields->mapWithKeys(fn ($field) => [$field->id => hash('sha256', json_encode($field->values))])->all(),
        ];
    }

    public function test_reorganizing_existing_fields_keeps_their_edited_content(): void
    {
        $field = ContentField::where('key', 'background_0')->firstOrFail();
        $field->update(['section' => 'settings', 'label' => 'Gambar latar 1', 'values' => array_fill_keys(['id', 'en', 'zh'], 'https://example.com/edited.jpg')]);
        app(CompanyPage::class)->import();
        $this->assertSame('home', $field->fresh()->section);
        $this->assertSame('https://example.com/edited.jpg', $field->fresh()->values['id']);
        $this->get('/')->assertSee('https://example.com/edited.jpg', false);
    }

    public function test_preview_requires_admin_and_does_not_publish_or_track(): void
    {
        $this->get('/admin/preview')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/preview')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/preview?lang=en')->assertOk()->assertSee('cms-bindings', false)->assertSee('cms-preview.js', false)->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertDatabaseCount('page_visits', 0);
        $this->get('/')->assertDontSee('cms-bindings', false)->assertDontSee('cms-preview.js', false);
    }

    public function test_uploaded_logo_and_favicon_reach_public_page_and_cms(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $logo = ContentField::where('label', 'Logo utama / header')->firstOrFail();
        $favicon = ContentField::where('key', 'brand_favicon')->firstOrFail();
        $payload = $this->payload('branding');
        $payload['uploads'] = [$logo->id => UploadedFile::fake()->image('brand.png'), $favicon->id => UploadedFile::fake()->image('favicon.png', 64, 64)];
        $this->put('/admin/content', $payload)->assertSessionHasNoErrors();
        foreach ([$logo, $favicon] as $field) {
            $url = $field->fresh()->values['id'];
            Storage::disk('public')->assertExists(substr($url, 9));
            $this->get('/')->assertSee($url, false);
            $this->get('/admin?section=branding')->assertSee($url, false);
        }
    }

    public function test_media_library_updates_background_and_rejects_invalid_favicon(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $field = ContentField::where('key', 'background_0')->firstOrFail();
        $payload = $this->payload('media');
        $payload['uploads'][$field->id] = UploadedFile::fake()->image('hero.webp');
        $this->put('/admin/content', $payload)->assertSessionHasNoErrors();
        $this->get('/')->assertSee($field->fresh()->values['id'], false);
        $favicon = ContentField::where('key', 'brand_favicon')->firstOrFail();
        $payload = $this->payload('branding');
        $payload['uploads'][$favicon->id] = UploadedFile::fake()->createWithContent('bad.svg', '<svg onload="alert(1)"></svg>');
        $this->put('/admin/content', $payload)->assertSessionHasErrors('uploads.'.$favicon->id);
        $payload['uploads'][$favicon->id] = UploadedFile::fake()->create('large.png', 2100, 'image/png');
        $this->put('/admin/content', $payload)->assertSessionHasErrors('uploads.'.$favicon->id);
    }

    public function test_real_visits_use_one_session_and_store_only_referrer_host(): void
    {
        $this->withSession(['analytics_visitor' => 'test-session']);
        $this->get('/?lang=id', ['User-Agent' => 'Mozilla/5.0 iPhone Mobile', 'Referer' => 'https://example.com/private/path?secret=abc'])->assertOk();
        $this->get('/?lang=en', ['User-Agent' => 'Mozilla/5.0 iPhone Mobile'])->assertOk();
        $this->assertDatabaseCount('page_visits', 2);
        $this->assertSame(1, DB::table('page_visits')->distinct()->count('visitor_hash'));
        $this->assertDatabaseHas('page_visits', ['referrer' => 'example.com', 'device' => 'Mobile', 'locale' => 'id']);
        $this->assertStringNotContainsString('secret', json_encode(DB::table('page_visits')->get()));
    }

    public function test_admin_bots_head_and_invalid_languages_are_not_counted(): void
    {
        $this->get('/', ['User-Agent' => 'Googlebot'])->assertOk();
        $this->head('/')->assertOk();
        $this->get('/?lang=xx')->assertNotFound();
        $this->actingAs($this->admin())->get('/')->assertOk();
        $this->get('/admin/preview')->assertOk();
        $this->assertDatabaseCount('page_visits', 0);
    }

    public function test_analytics_filter_and_unique_totals_are_correct(): void
    {
        $this->get('/admin/analytics')->assertRedirect('/admin/login');
        $this->actingAs($this->admin())->get('/admin/analytics')->assertOk()->assertSee('Belum ada kunjungan pada periode ini');
        foreach ([['a', now()], ['a', now()->subDay()], ['b', now()->subDays(12)], ['c', now()->subDays(95)]] as [$visitor, $date]) {
            DB::table('page_visits')->insert(['visitor_hash' => hash('sha256', $visitor), 'locale' => 'id', 'device' => 'Desktop', 'referrer' => null, 'visited_at' => $date]);
        }
        $this->get('/admin/analytics?days=7')->assertOk()->assertViewHas('total', 2)->assertViewHas('visitors', 1)->assertViewHas('today', 1);
        $this->get('/admin/analytics?days=30')->assertOk()->assertViewHas('total', 3)->assertViewHas('visitors', 2);
        $this->get('/admin/analytics?days=90')->assertOk()->assertViewHas('total', 3);
        $this->get('/admin/analytics?days=999')->assertStatus(422);
    }
}
