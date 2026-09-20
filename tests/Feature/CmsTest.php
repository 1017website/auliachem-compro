<?php

namespace Tests\Feature;

use App\Models\ContentField;
use App\Models\User;
use App\Services\CompanyPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
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

    private function payload(string $section): array
    {
        $payload = ['section' => $section, 'fields' => [], 'version' => []];
        foreach (ContentField::where('section', $section)->get() as $field) {
            $payload['fields'][$field->id] = $field->values;
            $payload['version'][$field->id] = hash('sha256', json_encode($field->values));
        }

        return $payload;
    }

    public function test_public_page_renders_all_languages_and_valid_navigation(): void
    {
        foreach (['id', 'en', 'zh'] as $locale) {
            $response = $this->get('/?lang='.$locale)->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString('lang="'.($locale === 'zh' ? 'zh-CN' : $locale).'"', $html);
            $this->assertStringNotContainsString('const TRANSLATIONS', $html);
            $this->assertStringContainsString('?auto=format&fit=crop&w=1500&q=88', $html);
            preg_match_all('/href="#([^"]+)"/', $html, $links);
            foreach ($links[1] as $anchor) {
                $this->assertStringContainsString('id="'.$anchor.'"', $html);
            }
        }
        $this->get('/?lang=invalid')->assertNotFound();
    }

    public function test_cms_requires_an_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->put('/admin/content', [])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'invalid@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/admin/login', ['email' => 'invalid@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_login_regenerates_session_and_logout_closes_access(): void
    {
        $admin = $this->admin();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_updates_persist_in_each_language_and_escape_html(): void
    {
        $field = ContentField::where('section', 'home')->where('type', 'text')->firstOrFail();
        $payload = $this->payload('home');
        $payload['fields'][$field->id] = ['id' => '<script>alert(1)</script> & Indonesia', 'en' => 'English changed', 'zh' => '更新内容'];
        $this->actingAs($this->admin())->put('/admin/content', $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/')->assertSee('<script>alert(1)</script> & Indonesia')->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/?lang=en')->assertSee('English changed');
        $this->get('/?lang=zh')->assertSee('更新内容');
        $this->assertSame(0, app(CompanyPage::class)->import());
        $this->assertSame('English changed', $field->fresh()->values['en']);
    }

    public function test_rejects_script_urls_and_conflicting_changes(): void
    {
        $field = ContentField::where('section', 'home')->where('type', 'link')->firstOrFail();
        $payload = $this->payload('home');
        $payload['fields'][$field->id]['id'] = 'javascript:alert(1)';
        $this->actingAs($this->admin())->put('/admin/content', $payload)->assertSessionHasErrors();
        $payload = $this->payload('home');
        $field->update(['values' => ['id' => '#quality', 'en' => '#quality', 'zh' => '#quality']]);
        $this->put('/admin/content', $payload)->assertSessionHasErrors('fields');
    }

    public function test_image_upload_is_saved_and_executable_files_are_rejected(): void
    {
        Storage::fake('public');
        $field = ContentField::where('type', 'image')->where('section', 'core')->firstOrFail();
        $payload = $this->payload('core');
        $payload['uploads'][$field->id] = UploadedFile::fake()->image('chemical.png');
        $this->actingAs($this->admin())->put('/admin/content', $payload)->assertSessionHasNoErrors();
        $url = $field->fresh()->values['id'];
        Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
        $this->get('/')->assertSee($url, false);
        $payload = $this->payload('core');
        $payload['uploads'][$field->id] = UploadedFile::fake()->create('shell.php', 1, 'application/x-php');
        $this->put('/admin/content', $payload)->assertSessionHasErrors('uploads.'.$field->id);
    }

    public function test_every_cms_section_renders(): void
    {
        $this->actingAs($this->admin());
        foreach (ContentField::distinct()->pluck('section') as $section) {
            $this->get('/admin?section='.$section)->assertOk()->assertSee('Simpan perubahan');
        }
    }

    public function test_developer_tools_are_hidden_from_regular_admins(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('Peralatan developer');
        $this->get('/admin/developer')->assertForbidden();
        $this->post('/admin/developer/run', ['tool' => 'clear-cache'])->assertForbidden();
    }

    public function test_developer_can_run_only_the_allowed_commands(): void
    {
        $developer = $this->admin();
        $developer->update(['role' => 'developer']);

        $this->actingAs($developer)->get('/admin/developer')->assertOk()->assertSee('Peralatan developer');
        $this->post('/admin/developer/run', ['tool' => 'anything'])->assertSessionHasErrors('tool');

        Artisan::shouldReceive('call')->once()->with('optimize:clear', [])->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Caches cleared successfully.');
        $this->post('/admin/developer/run', ['tool' => 'clear-cache'])
            ->assertRedirect()
            ->assertSessionHas('status', fn ($result) => $result['command'] === 'php artisan optimize:clear');
    }

    public function test_developer_can_manage_admins_without_exposing_developer_accounts(): void
    {
        $developer = $this->admin();
        $developer->update(['name' => 'Hidden Developer', 'role' => 'developer']);

        $this->actingAs($developer)->get('/admin/users')
            ->assertOk()
            ->assertSee('Manage users')
            ->assertDontSee($developer->email);

        $this->post('/admin/users', [
            'name' => 'Content Admin',
            'email' => 'content@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('cms.users.index'));

        $admin = User::where('email', 'content@example.com')->firstOrFail();
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertSame('admin', $admin->role);

        $this->put('/admin/users/'.$admin->id, [
            'name' => 'Updated Admin',
            'email' => 'updated@example.com',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('cms.users.index'));
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Updated Admin', 'email' => 'updated@example.com']);

        $this->delete('/admin/users/'.$admin->id)->assertRedirect(route('cms.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->get('/admin/users/'.$developer->id.'/edit')->assertNotFound();
    }

    public function test_regular_admin_cannot_manage_users(): void
    {
        $this->actingAs($this->admin())->get('/admin/users')->assertForbidden();
    }
}
