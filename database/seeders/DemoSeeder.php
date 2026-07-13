<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyVisitAssignment;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkSession;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $company = Company::factory()->create([
            'name_ar' => 'شركة اللدائن العالمية',
            'name_en' => 'Global Plastic Company (GPC)',
            'abbr' => 'GPC',
            'tax_number' => '618-549-994',
            'currency' => 'EGP',
            'vat_percent' => 14.00,
            'bank_name' => 'البنك الأهلي المصري',
            'bank_account' => '1234567890',
            'bank_iban' => 'EG123456789012345678901',
        ]);

        // Create bank account
        \App\Models\CompanyBankAccount::create([
            'company_id' => $company->id,
            'bank_name' => 'البنك الأهلي المصري',
            'account_name' => 'شركة اللدائن العالمية',
            'account_number' => '1234567890',
            'iban' => 'EG123456789012345678901',
            'is_default' => true,
        ]);

        // Create tax template
        $taxTemplate = \App\Models\TaxTemplate::create([
            'company_id' => $company->id,
            'name' => 'Standard VAT 14%',
            'type' => 'selling',
            'is_default' => true,
        ]);
        \App\Models\TaxTemplateLine::create([
            'tax_template_id' => $taxTemplate->id,
            'description' => 'Value Added Tax',
            'charge_type' => 'on_net_total',
            'rate' => 14.00,
        ]);

        // Create modes of payment
        foreach (['نقدي', 'شيك', 'تحويل بنكي', 'اعتماد مستندي', 'بطاقة ائتمان'] as $i => $name) {
            $types = ['cash', 'cheque', 'bank_transfer', 'lc', 'credit_card'];
            \App\Models\ModeOfPayment::create(['company_id' => $company->id, 'name' => $name, 'type' => $types[$i]]);
        }

        // Create price list
        \App\Models\PriceList::create([
            'company_id' => $company->id, 'name' => 'Standard', 'type' => 'selling', 'is_default' => true,
        ]);

        // Create customer group
        \App\Models\CustomerGroup::create([
            'company_id' => $company->id, 'name_ar' => 'تجاري', 'name_en' => 'Commercial',
        ]);

        // Create naming series
        foreach ([
            ['name' => 'sales_invoice', 'prefix' => 'INV', 'series_format' => 'INV-GPC-{YYYY}-{#####}', 'current_number' => 0],
            ['name' => 'proforma_invoice', 'prefix' => 'PF', 'series_format' => 'PF-GPC-{YYYY}-{#####}', 'current_number' => 0],
        ] as $ns) {
            \App\Models\NamingSeries::create($ns + ['company_id' => $company->id]);
        }

        // Create users
        $admin = User::factory()->create(['company_id' => $company->id, 'name' => 'عمرو حكيم', 'email' => 'admin@jawla.test', 'employee_code' => 'EMP-001'])->assignRole('admin');
        $manager = User::factory()->create(['company_id' => $company->id, 'name' => 'مدير المبيعات', 'email' => 'manager@jawla.test', 'employee_code' => 'EMP-002'])->assignRole('sales_manager');
        User::factory()->create(['company_id' => $company->id, 'name' => 'مالية', 'email' => 'accounts@jawla.test', 'employee_code' => 'EMP-003'])->assignRole('accounts');
        User::factory()->create(['company_id' => $company->id, 'name' => 'مشتريات', 'email' => 'purchasing@jawla.test', 'employee_code' => 'EMP-004'])->assignRole('purchasing');
        $warehouse = User::factory()->create(['company_id' => $company->id, 'name' => 'أمين المستودع', 'email' => 'warehouse@jawla.test', 'employee_code' => 'EMP-005'])->assignRole('warehouse_keeper');
        User::factory()->create(['company_id' => $company->id, 'name' => 'محمد طه', 'email' => 'executive@jawla.test', 'employee_code' => 'EMP-006'])->assignRole('executive');
        $rep = User::factory()->create(['company_id' => $company->id, 'name' => 'مندوب ١', 'email' => 'rep@jawla.test', 'employee_code' => 'EMP-007'])->assignRole('rep');

        // Create van warehouse for rep
        $vanWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'عربية مندوب ١',
            'name_en' => 'Rep 1 Van',
            'type' => 'van',
            'user_id' => $rep->id,
        ]);

        // Main warehouse
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'المخزن الرئيسي',
            'name_en' => 'Main Warehouse',
            'type' => 'main',
        ]);

        // Create categories
        $catPolymers = $company->productCategories()->create(['name_ar' => 'بوليمرات', 'name_en' => 'Polymers', 'sort_order' => 1]);
        $catChemicals = $company->productCategories()->create(['name_ar' => 'كيماويات', 'name_en' => 'Chemicals', 'sort_order' => 2]);

        // Create products
        $productPP = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catPolymers->id,
            'sku' => 'PP-H030', 'name_ar' => 'بولي بروبيلين H030', 'name_en' => 'Polypropylene H030',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 1000.00, 'cost' => 700.00,
        ]);
        $product952 = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catChemicals->id,
            'sku' => 'CHEM-952', 'name_ar' => 'مادة 952', 'name_en' => 'Material 952',
            'unit' => 'ton', 'packaging_type' => 'barrel', 'price' => 800.00, 'cost' => 500.00,
        ]);
        $productPE = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catPolymers->id,
            'sku' => 'PE-LD100', 'name_ar' => 'بولي إيثيلين LD100', 'name_en' => 'Polyethylene LD100',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 1200.00, 'cost' => 850.00,
        ]);

        // Add stock to van
        Stock::create(['warehouse_id' => $vanWarehouse->id, 'product_id' => $productPP->id, 'quantity' => 30]);
        Stock::create(['warehouse_id' => $vanWarehouse->id, 'product_id' => $productPE->id, 'quantity' => 20]);
        // Material 952 has no van stock → triggers out-of-stock

        // Add stock to main warehouse
        Stock::create(['warehouse_id' => $mainWarehouse->id, 'product_id' => $productPP->id, 'quantity' => 100]);
        Stock::create(['warehouse_id' => $mainWarehouse->id, 'product_id' => $productPE->id, 'quantity' => 80]);

        // Create route
        $route = Route::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'القاهرة - العبور',
            'name_en' => 'Cairo - Obour',
            'region' => 'القاهرة',
        ]);
        $route->users()->attach($rep);

        // Create customers
        $customers = [];
        $customerNames = [
            'مصنع الرواد للبلاستيك', 'شركة النيل للتغليف', 'مصنع الدلتا للصناعات',
            'الشركة المصرية للكيماويات', 'مصنع السلام للمنتجات البلاستيكية',
        ];
        foreach ($customerNames as $i => $name) {
            $customers[] = Customer::factory()->create([
                'company_id' => $company->id,
                'route_id' => $route->id,
                'code' => 'C-' . ($i + 1),
                'name_ar' => $name,
                'name_en' => 'Customer ' . ($i + 1),
                'phone' => '01' . fake()->numerify('0########'),
                'latitude' => 30.10 + ($i * 0.01),
                'longitude' => 31.30 + ($i * 0.01),
                'status' => 'approved',
            ]);
        }

        // Add one pending customer (for approval flow demo)
        Customer::factory()->create([
            'company_id' => $company->id,
            'route_id' => $route->id,
            'code' => 'C-PEND',
            'name_ar' => 'مصنع المستقبل (جديد)',
            'name_en' => 'Future Factory (New)',
            'phone' => '01099999999',
            'latitude' => 30.15,
            'longitude' => 31.35,
            'status' => 'pending',
            'added_by' => $rep->id,
        ]);

        // Create daily visit assignments (5 visits for today)
        foreach ($customers as $i => $customer) {
            DailyVisitAssignment::create([
                'company_id' => $company->id,
                'user_id' => $rep->id,
                'customer_id' => $customer->id,
                'visit_date' => today(),
                'status' => 'pending',
                'sort_order' => $i + 1,
                'assigned_by' => $manager->id,
            ]);
        }

        // Create work session for the rep
        WorkSession::create([
            'user_id' => $rep->id,
            'route_id' => $route->id,
            'started_at' => now()->subHours(2),
            'start_latitude' => 30.0444,
            'start_longitude' => 31.2357,
        ]);

        echo "Demo seeded:\n";
        echo "  Admin: admin@jawla.test / password\n";
        echo "  Manager: manager@jawla.test / password\n";
        echo "  Rep: rep@jawla.test / password\n";
        echo "  Finance: accounts@jawla.test / password\n";
        echo "  Warehouse: warehouse@jawla.test / password\n";
        echo "  Executive: executive@jawla.test / password\n";
    }
}