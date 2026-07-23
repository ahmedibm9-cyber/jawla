<?php

namespace Database\Seeders;

use App\Enums\StockReason;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\DailyVisitAssignment;
use App\Models\ModeOfPayment;
use App\Models\NamingSeries;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Route;
use App\Models\TaxTemplate;
use App\Models\TaxTemplateLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkSession;
use App\Services\Contracts\StockService as StockServiceContract;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // ─── Company ───────────────────────────────────────────────────
        // GPC (Global Plastic Company) — شركة اللدائن العالمية
        // Founded 2019 · 6th of October City, Giza · Raw-materials trading & distribution
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
            'address' => 'القطعة 218 – المنطقة الصناعية 3 – مدينة 6 أكتوبر – الجيزة',
            'phone' => '01070790207',
        ]);

        CompanyBankAccount::create([
            'company_id' => $company->id,
            'bank_name' => 'البنك الأهلي المصري',
            'account_name' => 'شركة اللدائن العالمية',
            'account_number' => '1234567890',
            'iban' => 'EG123456789012345678901',
            'is_default' => true,
        ]);

        // ─── Tax template ──────────────────────────────────────────────
        $taxTemplate = TaxTemplate::create([
            'company_id' => $company->id,
            'name' => 'ضريبة القيمة المضافة 14%',
            'type' => 'selling',
            'is_default' => true,
        ]);
        TaxTemplateLine::create([
            'tax_template_id' => $taxTemplate->id,
            'description' => 'Value Added Tax',
            'charge_type' => 'on_net_total',
            'rate' => 14.00,
        ]);

        // ─── Modes of payment ──────────────────────────────────────────
        foreach (['نقدي', 'شيك', 'تحويل بنكي', 'اعتماد مستندي', 'بطاقة ائتمان'] as $i => $name) {
            $types = ['cash', 'cheque', 'bank_transfer', 'lc', 'credit_card'];
            ModeOfPayment::create(['company_id' => $company->id, 'name' => $name, 'type' => $types[$i]]);
        }

        // ─── Price list ────────────────────────────────────────────────
        PriceList::create([
            'company_id' => $company->id,
            'name' => 'قائمة الأسعار الأساسية',
            'type' => 'selling',
            'is_default' => true,
        ]);

        // ─── Customer groups ───────────────────────────────────────────
        $grpIndustrial = CustomerGroup::create(['company_id' => $company->id, 'name_ar' => 'صناعي', 'name_en' => 'Industrial']);
        $grpCommercial = CustomerGroup::create(['company_id' => $company->id, 'name_ar' => 'تجاري', 'name_en' => 'Commercial']);
        $grpPackaging  = CustomerGroup::create(['company_id' => $company->id, 'name_ar' => 'تغليف', 'name_en' => 'Packaging']);

        // ─── Naming series ─────────────────────────────────────────────
        foreach ([
            ['name' => 'sales_invoice', 'prefix' => 'INV', 'series_format' => 'INV-GPC-{YYYY}-{#####}', 'current_number' => 0],
            ['name' => 'proforma_invoice', 'prefix' => 'PF', 'series_format' => 'PF-GPC-{YYYY}-{#####}', 'current_number' => 0],
            ['name' => 'purchase_order', 'prefix' => 'PO', 'series_format' => 'PO-GPC-{YYYY}-{#####}', 'current_number' => 0],
        ] as $ns) {
            NamingSeries::create($ns + ['company_id' => $company->id]);
        }

        // ─── Users ─────────────────────────────────────────────────────
        $admin    = User::factory()->create(['company_id' => $company->id, 'name' => 'عمرو حكيم', 'email' => 'admin@jawla.test', 'employee_code' => 'EMP-001'])->assignRole('admin');
        $manager  = User::factory()->create(['company_id' => $company->id, 'name' => 'مدير المبيعات', 'email' => 'manager@jawla.test', 'employee_code' => 'EMP-002'])->assignRole('sales_manager');
        User::factory()->create(['company_id' => $company->id, 'name' => 'مالية', 'email' => 'accounts@jawla.test', 'employee_code' => 'EMP-003'])->assignRole('accounts');
        User::factory()->create(['company_id' => $company->id, 'name' => 'مشتريات', 'email' => 'purchasing@jawla.test', 'employee_code' => 'EMP-004'])->assignRole('purchasing');
        $warehouseKeeper = User::factory()->create(['company_id' => $company->id, 'name' => 'أمين المستودع', 'email' => 'warehouse@jawla.test', 'employee_code' => 'EMP-005'])->assignRole('warehouse_keeper');
        User::factory()->create(['company_id' => $company->id, 'name' => 'محمد طه', 'email' => 'executive@jawla.test', 'employee_code' => 'EMP-006'])->assignRole('executive');
        $rep1 = User::factory()->create(['company_id' => $company->id, 'name' => 'أحمد سعيد', 'email' => 'rep@jawla.test', 'employee_code' => 'EMP-007'])->assignRole('rep');
        $rep2 = User::factory()->create(['company_id' => $company->id, 'name' => 'محمد علي', 'email' => 'rep2@jawla.test', 'employee_code' => 'EMP-008'])->assignRole('rep');

        // ─── Warehouses ────────────────────────────────────────────────
        $mainWarehouse = Warehouse::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'المخزن الرئيسي – 6 أكتوبر',
            'name_en' => 'Main Warehouse – 6th October',
            'type' => 'main',
        ]);

        $van1 = Warehouse::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'عربية أحمد سعيد',
            'name_en' => 'Ahmed Said Van',
            'type' => 'van',
            'user_id' => $rep1->id,
        ]);

        $van2 = Warehouse::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'عربية محمد علي',
            'name_en' => 'Mohamed Ali Van',
            'type' => 'van',
            'user_id' => $rep2->id,
        ]);

        // ─── Product categories ────────────────────────────────────────
        $catVirgin      = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'بوليمرات (خام)', 'name_en' => 'Virgin Polymers', 'sort_order' => 1]);
        $catRecycled    = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'بوليمرات (معاد تدويرها)', 'name_en' => 'Recycled Polymers', 'sort_order' => 2]);
        $catChemicals   = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'كيماويات', 'name_en' => 'Chemicals', 'sort_order' => 3]);
        $catPackaging   = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'مواد تغليف', 'name_en' => 'Packaging Materials', 'sort_order' => 4]);

        // ─── Products — Virgin Polymers ────────────────────────────────
        // Sourced from SABIC, ExxonMobil, TASNEE, OQ, Borouge
        $products = [];

        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PP-H030', 'name_ar' => 'بولي بروبيلين H030 (سايبم)', 'name_en' => 'Polypropylene H030 (Sabic)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 42500.00, 'cost' => 38000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PP-H530', 'name_ar' => 'بولي بروبيلين H530 (سايبم)', 'name_en' => 'Polypropylene H530 (Sabic)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 43000.00, 'cost' => 38500.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PE-HD56S', 'name_ar' => 'بولي إيثيلين عالي الكثافة HD56S (إكسون)', 'name_en' => 'HDPE HD56S (ExxonMobil)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 46000.00, 'cost' => 41000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PE-HD6760', 'name_ar' => 'بولي إيثيلين عالي الكثافة HD6760 (بوروج)', 'name_en' => 'HDPE HD6760 (Borouge)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 47500.00, 'cost' => 42000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PE-LD200', 'name_ar' => 'بولي إيثيلين منخفض الكثافة LD200 (إكسون)', 'name_en' => 'LDPE LD200 (ExxonMobil)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 44500.00, 'cost' => 39500.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PE-LLD0209', 'name_ar' => 'بولي إيثيلين منخفض الكثافة线性 LLDPE 0209 (تاسني)', 'name_en' => 'LLDPE 0209 (TASNEE)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 43500.00, 'cost' => 39000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PVC-S65', 'name_ar' => 'بولي فينيل كلوريد S65 (إكيبلاست)', 'name_en' => 'PVC S65 (EGPlast)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 38000.00, 'cost' => 34000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PET-REG', 'name_ar' => 'بولي إيثيلين تيريثالات (بيت) PET (بوروج)', 'name_en' => 'PET Resin (Borouge)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 41000.00, 'cost' => 36500.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PS-GPPS', 'name_ar' => 'بولي ستايرين GPPS (إكسون)', 'name_en' => 'Polystyrene GPPS (ExxonMobil)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 40000.00, 'cost' => 35500.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catVirgin->id,
            'sku' => 'VIR-PC-1000', 'name_ar' => 'بولي كربونات 1000 (إكويت)', 'name_en' => 'Polycarbonate 1000 (OQ)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 85000.00, 'cost' => 76000.00,
        ]);

        // ─── Products — Recycled Polymers ──────────────────────────────
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catRecycled->id,
            'sku' => 'REC-rPP-01', 'name_ar' => 'بولي بروبيلين معاد تدويره r-PP', 'name_en' => 'Recycled Polypropylene r-PP',
            'unit' => 'ton', 'packaging_type' => 'jumbo_bag', 'price' => 28000.00, 'cost' => 24000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catRecycled->id,
            'sku' => 'REC-rPE-01', 'name_ar' => 'بولي إيثيلين معاد تدويره r-PE', 'name_en' => 'Recycled Polyethylene r-PE',
            'unit' => 'ton', 'packaging_type' => 'jumbo_bag', 'price' => 26000.00, 'cost' => 22000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catRecycled->id,
            'sku' => 'REC-rPET-01', 'name_ar' => 'بولي إيثيلين تيريثالات معاد تدويره r-PET', 'name_en' => 'Recycled PET r-PET',
            'unit' => 'ton', 'packaging_type' => 'jumbo_bag', 'price' => 30000.00, 'cost' => 25500.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catRecycled->id,
            'sku' => 'REC-rPVC-01', 'name_ar' => 'بولي فينيل كلوريد معاد تدويره r-PVC', 'name_en' => 'Recycled PVC r-PVC',
            'unit' => 'ton', 'packaging_type' => 'jumbo_bag', 'price' => 22000.00, 'cost' => 18500.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catRecycled->id,
            'sku' => 'REC-rABS-01', 'name_ar' => 'أكريلونتريل بوتادين ستايرين معاد تدويره r-ABS', 'name_en' => 'Recycled ABS r-ABS',
            'unit' => 'ton', 'packaging_type' => 'jumbo_bag', 'price' => 35000.00, 'cost' => 30000.00,
        ]);

        // ─── Products — Chemicals (Golden Power Chemicals partnership) ──
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catChemicals->id,
            'sku' => 'CHM-CACO3', 'name_ar' => 'كربونات كالسيوم (حشو بلاستيكي)', 'name_en' => 'Calcium Carbonate (Plastic Filler)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 8500.00, 'cost' => 6200.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catChemicals->id,
            'sku' => 'CHM-TIO2', 'name_ar' => 'أكسيد تيتانيوم (تايتن)', 'name_en' => 'Titanium Dioxide (TiO2)',
            'unit' => 'ton', 'packaging_type' => 'bag', 'price' => 95000.00, 'cost' => 85000.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catChemicals->id,
            'sku' => 'CHM-STEARATE', 'name_ar' => 'ستيرات كالسيوم', 'name_en' => 'Calcium Stearate',
            'unit' => 'kg', 'packaging_type' => 'bag', 'price' => 120.00, 'cost' => 95.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catChemicals->id,
            'sku' => 'CHM-UV-STAB', 'name_ar' => 'مثبت الأشعة فوق البنفسجية UV Stabilizer', 'name_en' => 'UV Stabilizer',
            'unit' => 'kg', 'packaging_type' => 'drum', 'price' => 350.00, 'cost' => 280.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catChemicals->id,
            'sku' => 'CHM-PEROXIDE', 'name_ar' => 'بيرأكسيد (مبيد للحرارة)', 'name_en' => 'Peroxide (Heat Stabilizer)',
            'unit' => 'kg', 'packaging_type' => 'drum', 'price' => 180.00, 'cost' => 140.00,
        ]);

        // ─── Products — Packaging Materials ────────────────────────────
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catPackaging->id,
            'sku' => 'PKG-SHRINK', 'name_ar' => 'شرينك رول (لف بلاستيك)', 'name_en' => 'Shrink Roll Film',
            'unit' => 'kg', 'packaging_type' => 'other', 'price' => 65.00, 'cost' => 48.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catPackaging->id,
            'sku' => 'PKG-POPP', 'name_ar' => 'فيلم POPP (بولي بروبيلين شفاف)', 'name_en' => 'OPP Film',
            'unit' => 'kg', 'packaging_type' => 'other', 'price' => 75.00, 'cost' => 55.00,
        ]);
        $products[] = Product::factory()->create([
            'company_id' => $company->id, 'category_id' => $catPackaging->id,
            'sku' => 'PKG-STRETCH', 'name_ar' => 'فيلم استرتش', 'name_en' => 'Stretch Film',
            'unit' => 'kg', 'packaging_type' => 'other', 'price' => 58.00, 'cost' => 42.00,
        ]);

        // ─── Routes ────────────────────────────────────────────────────
        $routeCairo = Route::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'القاهرة – العبور – مصر الجديدة',
            'name_en' => 'Cairo – Obour – Heliopolis',
            'region' => 'القاهرة',
        ]);
        $routeGiza = Route::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'الجيزة – 6 أكتوبر – الشيخ زايد',
            'name_en' => 'Giza – 6th October – Sheikh Zayed',
            'region' => 'الجيزة',
        ]);
        $routeAlex = Route::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'الإسكندرية – برج العرب –琈يد',
            'name_en' => 'Alexandria – Borg El Arab – Foumity',
            'region' => 'الإسكندرية',
        ]);

        $routeCairo->users()->attach($rep1);
        $routeGiza->users()->attach($rep2);
        $routeAlex->users()->attach($rep1);

        // ─── Customers ─────────────────────────────────────────────────
        // Realistic Egyptian plastics & packaging companies across routes
        $customers = [];

        // Cairo route customers
        $cairoCustomers = [
            ['code' => 'C-001', 'name_ar' => 'مصنع الرواد للبلاستيك', 'name_en' => 'Al Rowad Plastics Factory', 'phone' => '01012345678', 'lat' => 30.0890, 'lng' => 31.3378, 'group' => $grpIndustrial, 'credit' => 500000],
            ['code' => 'C-002', 'name_ar' => 'شركة النيل للتغليف', 'name_en' => 'Nile Packaging Co.', 'phone' => '01023456789', 'lat' => 30.0950, 'lng' => 31.3420, 'group' => $grpPackaging, 'credit' => 750000],
            ['code' => 'C-003', 'name_ar' => 'مصنع الدلتا للصناعات البلاستيكية', 'name_en' => 'Delta Plastics Industries', 'phone' => '01034567890', 'lat' => 30.0780, 'lng' => 31.3210, 'group' => $grpIndustrial, 'credit' => 400000],
            ['code' => 'C-004', 'name_ar' => 'الشركة المصرية للكيماويات', 'name_en' => 'Egyptian Chemicals Co.', 'phone' => '01045678901', 'lat' => 30.1020, 'lng' => 31.3550, 'group' => $grpCommercial, 'credit' => 600000],
            ['code' => 'C-005', 'name_ar' => 'مصنع السلام للمنتجات البلاستيكية', 'name_en' => 'Salam Plastics Products', 'phone' => '01056789012', 'lat' => 30.0710, 'lng' => 31.3080, 'group' => $grpIndustrial, 'credit' => 350000],
        ];

        foreach ($cairoCustomers as $c) {
            $customers[] = Customer::factory()->create([
                'company_id' => $company->id, 'route_id' => $routeCairo->id,
                'code' => $c['code'], 'name_ar' => $c['name_ar'], 'name_en' => $c['name_en'],
                'phone' => $c['phone'], 'latitude' => $c['lat'], 'longitude' => $c['lng'],
                'customer_group_id' => $c['group']->id, 'credit_limit' => $c['credit'],
                'status' => 'approved',
            ]);
        }

        // Giza route customers
        $gizaCustomers = [
            ['code' => 'C-006', 'name_ar' => 'شركة أبوعبود للصناعات البلاستيكية', 'name_en' => 'Abu Ghaly Plastics', 'phone' => '01112345678', 'lat' => 30.0131, 'lng' => 31.2089, 'group' => $grpIndustrial, 'credit' => 450000],
            ['code' => 'C-007', 'name_ar' => 'مصنع الهرم للبلاستيك', 'name_en' => 'Pyramid Plastics Factory', 'phone' => '01123456789', 'lat' => 29.9872, 'lng' => 31.1378, 'group' => $grpPackaging, 'credit' => 300000],
            ['code' => 'C-008', 'name_ar' => 'شركة هايما بلاستيك', 'name_en' => 'Hyma Plastic', 'phone' => '01134567890', 'lat' => 30.0561, 'lng' => 31.1840, 'group' => $grpIndustrial, 'credit' => 550000],
            ['code' => 'C-009', 'name_ar' => 'مصنع العبور للصناعات التغليفية', 'name_en' => 'Obour Packaging Industries', 'phone' => '01145678901', 'lat' => 30.0240, 'lng' => 31.2210, 'group' => $grpPackaging, 'credit' => 400000],
        ];

        foreach ($gizaCustomers as $c) {
            $customers[] = Customer::factory()->create([
                'company_id' => $company->id, 'route_id' => $routeGiza->id,
                'code' => $c['code'], 'name_ar' => $c['name_ar'], 'name_en' => $c['name_en'],
                'phone' => $c['phone'], 'latitude' => $c['lat'], 'longitude' => $c['lng'],
                'customer_group_id' => $c['group']->id, 'credit_limit' => $c['credit'],
                'status' => 'approved',
            ]);
        }

        // Alexandria route customers
        $alexCustomers = [
            ['code' => 'C-010', 'name_ar' => 'شركة الإسكندرية للبلاستيك والكيماويات', 'name_en' => 'Alex Plastics & Chemicals', 'phone' => '01212345678', 'lat' => 31.2001, 'lng' => 29.9187, 'group' => $grpCommercial, 'credit' => 600000],
            ['code' => 'C-011', 'name_ar' => 'مصنع برج العرب للصناعات البلاستيكية', 'name_en' => 'Borg El Arab Plastics', 'phone' => '01223456789', 'lat' => 30.8576, 'lng' => 29.5840, 'group' => $grpIndustrial, 'credit' => 350000],
            ['code' => 'C-012', 'name_ar' => 'شركة النصر للتغليف', 'name_en' => 'Nasser Packaging Co.', 'phone' => '01234567890', 'lat' => 31.1956, 'lng' => 29.9010, 'group' => $grpPackaging, 'credit' => 250000],
        ];

        foreach ($alexCustomers as $c) {
            $customers[] = Customer::factory()->create([
                'company_id' => $company->id, 'route_id' => $routeAlex->id,
                'code' => $c['code'], 'name_ar' => $c['name_ar'], 'name_en' => $c['name_en'],
                'phone' => $c['phone'], 'latitude' => $c['lat'], 'longitude' => $c['lng'],
                'customer_group_id' => $c['group']->id, 'credit_limit' => $c['credit'],
                'status' => 'approved',
            ]);
        }

        // Pending customer (for approval flow demo)
        Customer::factory()->create([
            'company_id' => $company->id, 'route_id' => $routeCairo->id,
            'code' => 'C-PEND', 'name_ar' => 'مصنع المستقبل البلاستيكي (جديد)', 'name_en' => 'Future Plastics Factory (New)',
            'phone' => '01099999999', 'latitude' => 30.0650, 'longitude' => 31.3100,
            'status' => 'pending', 'added_by' => $rep1->id,
        ]);

        // ─── Stock — Main warehouse (all products) ─────────────────────
        $stockService = app(StockServiceContract::class);

        // Give each product reasonable stock in the main warehouse
        $stockQties = [
            'VIR-PP-H030' => 120, 'VIR-PP-H530' => 80, 'VIR-PE-HD56S' => 95,
            'VIR-PE-HD6760' => 60, 'VIR-PE-LD200' => 110, 'VIR-PE-LLD0209' => 85,
            'VIR-PVC-S65' => 70, 'VIR-PET-REG' => 50, 'VIR-PS-GPPS' => 40,
            'VIR-PC-1000' => 25,
            'REC-rPP-01' => 60, 'REC-rPE-01' => 55, 'REC-rPET-01' => 45,
            'REC-rPVC-01' => 30, 'REC-rABS-01' => 20,
            'CHM-CACO3' => 200, 'CHM-TIO2' => 15, 'CHM-STEARATE' => 500,
            'CHM-UV-STAB' => 300, 'CHM-PEROXIDE' => 250,
            'PKG-SHRINK' => 800, 'PKG-POPP' => 600, 'PKG-STRETCH' => 700,
        ];

        foreach ($products as $product) {
            $qty = $stockQties[$product->sku] ?? 50;
            $stockService->increment($mainWarehouse->id, $product->id, null, $qty, StockReason::Initial, $mainWarehouse);
        }

        // Van stock — rep 1 (top sellers for the route)
        $van1Products = array_filter($products, fn($p) => in_array($p->sku, ['VIR-PP-H030', 'VIR-PE-HD56S', 'VIR-PE-LD200', 'CHM-CACO3', 'PKG-SHRINK']));
        foreach ($van1Products as $p) {
            $stockService->increment($van1->id, $p->id, null, 10, StockReason::Initial, $van1);
        }

        // Van stock — rep 2
        $van2Products = array_filter($products, fn($p) => in_array($p->sku, ['VIR-PP-H030', 'VIR-PVC-S65', 'REC-rPP-01', 'CHM-STEARATE', 'PKG-POPP']));
        foreach ($van2Products as $p) {
            $stockService->increment($van2->id, $p->id, null, 8, StockReason::Initial, $van2);
        }

        // ─── Daily visit assignments ───────────────────────────────────
        foreach ($customers as $i => $customer) {
            $assignedRep = $customer->route_id === $routeCairo->id ? $rep1 : ($customer->route_id === $routeGiza->id ? $rep2 : $rep1);
            DailyVisitAssignment::create([
                'company_id' => $company->id,
                'user_id' => $assignedRep->id,
                'customer_id' => $customer->id,
                'visit_date' => today(),
                'status' => 'pending',
                'sort_order' => $i + 1,
                'assigned_by' => $manager->id,
            ]);
        }

        // ─── Work sessions ─────────────────────────────────────────────
        WorkSession::create([
            'user_id' => $rep1->id,
            'route_id' => $routeCairo->id,
            'started_at' => now()->subHours(2),
            'start_latitude' => 30.0444,
            'start_longitude' => 31.2357,
        ]);

        echo "GPC demo seeded:\n";
        echo "  Company: شركة اللدائن العالمية (GPC)\n";
        echo "  Products: " . count($products) . " across 4 categories\n";
        echo "  Customers: " . count($customers) . " across 3 routes\n";
        echo "  Admin: admin@jawla.test / password\n";
        echo "  Manager: manager@jawla.test / password\n";
        echo "  Rep 1: rep@jawla.test / password\n";
        echo "  Rep 2: rep2@jawla.test / password\n";
        echo "  Finance: accounts@jawla.test / password\n";
        echo "  Warehouse: warehouse@jawla.test / password\n";
        echo "  Executive: executive@jawla.test / password\n";
    }
}
