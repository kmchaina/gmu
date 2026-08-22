<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
            'department' => 'Grants Management Unit',
        ];
    }

    /**
     * Give the user a GMU role, creating the role record if it does not exist.
     */
    public function role(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(
                ['name' => $name],
                ['display_name' => Str::headline($name)]
            )->id,
        ]);
    }

    public function coordinator(): static     { return $this->role('coordinator'); }
    public function planningOfficer(): static { return $this->role('planning_officer'); }
    public function financeOfficer(): static  { return $this->role('finance_officer'); }
    public function legalOfficer(): static    { return $this->role('legal_officer'); }
    public function researchScientist(): static { return $this->role('research_scientist'); }
    public function principalInvestigator(): static { return $this->role('pi'); }
    public function secretary(): static       { return $this->role('secretary'); }

    /**
     * A deactivated account.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
