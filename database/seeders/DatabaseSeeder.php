<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $company = Company::factory()->create();

        $demoUsers = [
            'system_viewer' => 'viewer@jawla.test',
            'hr_admin' => 'hr@jawla.test',
            'sales_manager' => 'manager@jawla.test',
            'warehouse_keeper' => 'warehouse@jawla.test',
            'sales_rep' => 'rep@jawla.test',
        ];

        foreach ($demoUsers as $role => $email) {
            $user = User::factory()->create([
                'company_id' => $company->id,
                'name' => ucfirst(str_replace('_', ' ', $role)),
                'email' => $email,
            ]);

            $user->assignRole($role);
        }
    }
}
