<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates test accounts for client UAT on staging.
 *
 * Usage:  php artisan db:seed --class=ClientTestSeeder
 *
 * Safe to run multiple times — uses findOrCreate by email.
 */
class ClientTestSeeder extends Seeder
{
    private const PASSWORD = '123456789';

    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['abbr' => 'GPC'],
            [
                'name_ar' => 'شركة جولة للتجارة والتوزيع',
                'name_en' => 'GPC Trading & Distribution',
                'currency' => 'EGP',
                'vat_percent' => 14.00,
            ]
        );

        $accounts = [
            [
                'name' => 'Admin',
                'email' => 'admin@jawla.test',
                'employee_code' => 'UAT-001',
                'roles' => ['admin', 'hr_admin'],
            ],
            [
                'name' => 'Sales Manager',
                'email' => 'sales@jawla.test',
                'employee_code' => 'UAT-002',
                'roles' => ['sales_manager'],
            ],
            [
                'name' => 'Sales Rep',
                'email' => 'rep@jawla.test',
                'employee_code' => 'UAT-003',
                'roles' => ['sales_rep', 'rep'],
            ],
            [
                'name' => 'Warehouse',
                'email' => 'warehouse@jawla.test',
                'employee_code' => 'UAT-004',
                'roles' => ['warehouse_keeper'],
            ],
        ];

        $created = [];

        foreach ($accounts as $data) {
            $roles = $data['roles'];
            unset($data['roles']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                $data + [
                    'company_id' => $company->id,
                    'password' => Hash::make(self::PASSWORD),
                    'is_active' => true,
                ]
            );

            $user->syncRoles($roles);
            $created[$data['email']] = self::PASSWORD;
        }

        $this->command->info('');
        $this->command->info('=== Client Test Accounts ===');
        $this->command->info('Password for all: '.self::PASSWORD);
        $this->command->table(['Email', 'Role'], collect($accounts)->map(fn ($a) => [$a['email'], implode(', ', $a['roles'])])->toArray());
        $this->command->info('');
    }
}
