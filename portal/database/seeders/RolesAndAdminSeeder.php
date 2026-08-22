<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
            \App\Models\Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $coordinatorRole = \App\Models\Role::where('name', 'coordinator')->first();

        \App\Models\User::firstOrCreate(
            ['email' => 'admin@nimr.or.tz'],
            [
                'name'       => 'GMU Administrator',
                'email'      => 'admin@nimr.or.tz',
                'password'   => \Illuminate\Support\Facades\Hash::make('Admin@NIMR2026'),
                'role_id'    => $coordinatorRole->id,
                'department' => 'Grants Management Unit',
                'is_active'  => true,
            ]
        );
    }
}
