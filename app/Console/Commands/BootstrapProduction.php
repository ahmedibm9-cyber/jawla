<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapProduction extends Command
{
    protected $signature = 'app:bootstrap-production
        {--confirm= : Must be exactly BOOTSTRAP}
        {--company-name-en= : Legal company name in English}
        {--company-name-ar= : Legal company name in Arabic}
        {--tax-number= : Unique company tax number}
        {--admin-name= : Initial administrator name}
        {--admin-email= : Initial administrator email}';

    protected $description = 'One-time secure bootstrap for an empty production database';

    public function handle(): int
    {
        if (config('jawla.mode') !== 'production') {
            $this->error('Production bootstrap is available only when JAWLA_MODE=production.');

            return self::FAILURE;
        }

        if ($this->option('confirm') !== 'BOOTSTRAP') {
            $this->error('Refusing bootstrap without --confirm=BOOTSTRAP.');

            return self::FAILURE;
        }

        if (Company::query()->exists() || User::query()->exists()) {
            $this->error('Refusing bootstrap because the database is not empty.');

            return self::FAILURE;
        }

        $input = [
            'company_name_en' => $this->option('company-name-en'),
            'company_name_ar' => $this->option('company-name-ar'),
            'tax_number' => $this->option('tax-number'),
            'admin_name' => $this->option('admin-name'),
            'admin_email' => $this->option('admin-email'),
            'password' => getenv('JAWLA_BOOTSTRAP_ADMIN_PASSWORD') ?: null,
        ];

        $validator = Validator::make($input, [
            'company_name_en' => ['required', 'string', 'max:255'],
            'company_name_ar' => ['required', 'string', 'max:255'],
            'tax_number' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:16',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($input): void {
            $this->callSilently('db:seed', [
                '--class' => RoleSeeder::class,
                '--force' => true,
            ]);

            $company = Company::create([
                'name_en' => $input['company_name_en'],
                'name_ar' => $input['company_name_ar'],
                'tax_number' => $input['tax_number'],
                'currency' => 'EGP',
                'vat_percent' => 14,
                'is_active' => true,
            ]);

            app(ActiveCompanyContext::class)->runWithCompany($company->id, function () use ($company, $input): void {
                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $input['admin_name'],
                    'email' => strtolower($input['admin_email']),
                    'employee_code' => 'SETUP-001',
                    'password' => Hash::make($input['password']),
                    'is_active' => true,
                ]);

                $user->companies()->syncWithoutDetaching([$company->id]);
                $user->assignRole(['sales_manager', 'hr_admin', 'warehouse_keeper']);
            });
        });

        $this->info('Production bootstrap completed. Remove JAWLA_BOOTSTRAP_ADMIN_PASSWORD from the environment now.');

        return self::SUCCESS;
    }
}
