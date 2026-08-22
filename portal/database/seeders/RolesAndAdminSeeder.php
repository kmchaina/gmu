<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Seed the GMU roles and the first Coordinator account.
     *
     * Audit finding H4: this used to hardcode the administrator password, so
     * every copy of the source carried a working credential for every install.
     * The password now comes from GMU_ADMIN_PASSWORD, or is generated and
     * printed once, and the account cannot be used until it is changed.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'coordinator',        'display_name' => 'GMU Coordinator',           'description' => 'Full system access; oversees all grant activities'],
            ['name' => 'planning_officer',   'display_name' => 'Planning Officer',           'description' => 'Posts and manages funding calls and opportunities'],
            ['name' => 'finance_officer',    'display_name' => 'Finance Officer',            'description' => 'Reviews budgets and tracks financial expenditures'],
            ['name' => 'legal_officer',      'display_name' => 'Legal & Compliance Officer', 'description' => 'Reviews contracts and donor compliance requirements'],
            ['name' => 'research_scientist', 'display_name' => 'Research Scientist',         'description' => 'Conducts technical reviews of proposals'],
            ['name' => 'pi',                 'display_name' => 'Principal Investigator',     'description' => 'Lead researcher on grant proposals and awards'],
            ['name' => 'secretary',          'display_name' => 'Secretary / Admin',          'description' => 'Administrative support and document management'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $email = env('GMU_ADMIN_EMAIL', 'admin@nimr.or.tz');

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->command?->info("Administrator {$email} already exists — password left untouched.");

            return;
        }

        $password  = env('GMU_ADMIN_PASSWORD');
        $generated = $password === null;
        $password ??= Str::password(20, symbols: false);

        User::create([
            'name'                 => 'GMU Administrator',
            'email'                => $email,
            'password'             => Hash::make($password),
            'role_id'              => Role::where('name', 'coordinator')->value('id'),
            'department'           => 'Grants Management Unit',
            'is_active'            => true,
            'must_change_password' => true,
        ]);

        $this->command?->newLine();
        $this->command?->info("GMU Coordinator account created: {$email}");

        if ($generated) {
            $this->command?->warn("Generated password: {$password}");
            $this->command?->comment('This is shown once. Sign in with it and set your own password immediately.');
        } else {
            $this->command?->comment('Password taken from GMU_ADMIN_PASSWORD. It must be changed at first sign-in.');
        }

        $this->command?->newLine();
    }
}
