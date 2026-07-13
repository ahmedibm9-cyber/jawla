<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $company = Company::factory()->create([
            'name_ar' => 'شركة اللدائن العالمية',
            'name_en' => 'Global Plastic Company (GPC)',
            'abbr' => 'GPC',
            'tax_number' => '618-549-994',
        ]);

        $demoUsers = [
            'admin' => 'admin@jawla.test',
            'sales_manager' => 'manager@jawla.test',
            'accounts' => 'accounts@jawla.test',
            'purchasing' => 'purchasing@jawla.test',
            'warehouse_keeper' => 'warehouse@jawla.test',
            'executive' => 'executive@jawla.test',
            'rep' => 'rep@jawla.test',
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