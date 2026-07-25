<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SeedSuperAdmin extends Command
{
    protected $signature = 'app:seed-super-admin';

    protected $description = 'Create the super admin account if it does not exist';

    public function handle(): int
    {
        // Ensure the super_admin role exists
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->info('super_admin role ensured.');

        $email = 'superadmin@jawla.test';

        if (User::where('email', $email)->exists()) {
            $this->info("Super admin already exists: {$email}");

            return self::SUCCESS;
        }

        $company = Company::first();
        if (! $company) {
            $this->error('No company found. Seed the demo data first.');

            return self::FAILURE;
        }

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Super Admin',
            'email' => $email,
            'employee_code' => 'EMP-000',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');

        $this->info("Super admin created: {$email} / password");

        return self::SUCCESS;
    }
}
