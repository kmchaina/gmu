<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * NIMR has not taken up the external grants modules, so they are switched off
 * (config gmu.external_grants). Switched off, they must be out of the menu and
 * unreachable by URL, while the rest of the portal keeps working.
 */
class ExternalGrantsSwitchTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    public function test_switched_off_modules_answer_404(): void
    {
        config(['gmu.external_grants' => false]);
        $coordinator = $this->user('coordinator');

        foreach (['calls.index', 'proposals.index', 'awards.index', 'reports.index', 'documents.index',
                  'donor-policies.index', 'donors.index', 'library.index', 'researchers.index', 'analytics'] as $route) {
            $this->actingAs($coordinator)->get(route($route))->assertNotFound();
        }
    }

    public function test_switched_off_modules_leave_the_menu_and_the_dashboard(): void
    {
        config(['gmu.external_grants' => false]);
        $coordinator = $this->user('coordinator');

        $this->actingAs($coordinator)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('calls.index'))
            ->assertDontSee(route('awards.index'))
            ->assertSee(route('admin.users.index'));
    }

    public function test_switched_on_modules_are_reachable(): void
    {
        config(['gmu.external_grants' => true]);
        $coordinator = $this->user('coordinator');

        $this->actingAs($coordinator)->get(route('calls.index'))->assertOk();
    }
}
