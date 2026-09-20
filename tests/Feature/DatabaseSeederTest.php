<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_company_content_and_the_bootstrap_developer(): void
    {
        config()->set('cms.bootstrap_user', [
            'name' => 'Deployment Developer',
            'email' => 'developer@example.com',
            'password' => 'A-secure-password-123',
        ]);

        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'developer@example.com')->firstOrFail();

        $this->assertSame('Deployment Developer', $user->name);
        $this->assertSame('developer', $user->role);
        $this->assertTrue((bool) $user->is_admin);
        $this->assertTrue((bool) $user->is_active);
        $this->assertTrue(Hash::check('A-secure-password-123', $user->password));
        $this->assertDatabaseCount('content_fields', 207);
    }

    public function test_reseeding_does_not_overwrite_an_existing_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Existing Developer',
            'email' => 'developer@example.com',
            'password' => 'Existing-password-123',
            'role' => 'developer',
            'is_admin' => true,
        ]);

        config()->set('cms.bootstrap_user', [
            'name' => 'Replacement Name',
            'email' => 'developer@example.com',
            'password' => 'Replacement-password-123',
        ]);

        $this->seed(DatabaseSeeder::class);

        $user->refresh();
        $this->assertSame('Existing Developer', $user->name);
        $this->assertTrue(Hash::check('Existing-password-123', $user->password));
    }

    public function test_existing_active_cms_user_allows_seeding_without_bootstrap_credentials(): void
    {
        User::factory()->create([
            'role' => 'developer',
            'is_admin' => true,
            'is_active' => true,
        ]);
        config()->set('cms.bootstrap_user', [
            'name' => null,
            'email' => null,
            'password' => null,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('content_fields', 207);
    }
}
