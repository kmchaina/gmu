<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * `.env.example` ships `GMU_ADMIN_PASSWORD=` empty. That must mean "generate
 * one", never "the password is the empty string".
 */
class AdminSeedPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function withAdminPassword(?string $value): void
    {
        foreach ([&$_ENV, &$_SERVER] as &$bag) {
            if ($value === null) {
                unset($bag['GMU_ADMIN_PASSWORD']);
            } else {
                $bag['GMU_ADMIN_PASSWORD'] = $value;
            }
        }
        putenv($value === null ? 'GMU_ADMIN_PASSWORD' : 'GMU_ADMIN_PASSWORD=' . $value);
    }

    protected function tearDown(): void
    {
        $this->withAdminPassword(null);
        parent::tearDown();
    }

    public function test_an_empty_setting_generates_a_password_instead_of_a_blank_one(): void
    {
        $this->withAdminPassword('');

        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'coordinator'))->firstOrFail();
        $this->assertFalse(Hash::check('', $admin->password));
        $this->assertTrue($admin->must_change_password);
    }

    public function test_a_set_password_is_used(): void
    {
        $this->withAdminPassword('a-long-local-test-value');

        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'coordinator'))->firstOrFail();
        $this->assertTrue(Hash::check('a-long-local-test-value', $admin->password));
    }
}
