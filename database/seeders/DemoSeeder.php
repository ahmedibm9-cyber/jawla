<?php

namespace Database\Seeders;

use App\Enums\StockReason;
use App\Models\Alarm;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\DailyVisitAssignment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ModeOfPayment;
use App\Models\NamingSeries;
use App\Models\Payment;
use App\Models\PriceList;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\ReturnItem;
use App\Models\ReturnRecord;
use App\Models\Route;
use App\Models\Supplier;
use App\Models\TaxTemplate;
use App\Models\TaxTemplateLine;
use App\Models\User;
use App\Models\Visit;
use App\Models\Warehouse;
use App\Models\WorkSession;
use App\Services\Contracts\StockService as StockServiceContract;
use App\Services\NumberSequenceService;
use App\Support\ActiveCompanyContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('jawla.mode') !== 'demo') {
            throw new \LogicException('DemoSeeder may run only when JAWLA_MODE=demo.');
        }

        echo "\n=== DEMO SEEDER RUNNING ===\n";
        $this->call(RoleSeeder::class);

        $company = Company::where('name_en', 'Global Plastic Company (GPC)')->first();
        if ($company !== null) {
            app(ActiveCompanyContext::class)->setCompanyId($company->id);
        }
        $alreadySeeded = Product::where('company_id', $company?->id)->exists();

        if (! $alreadySeeded) {
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
            app(ActiveCompanyContext::class)->setCompanyId($company->id);

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
            $grpPackaging = CustomerGroup::create(['company_id' => $company->id, 'name_ar' => 'تغليف', 'name_en' => 'Packaging']);

            // ─── Naming series ─────────────────────────────────────────────
            foreach ([
                ['name' => 'sales_invoice', 'prefix' => 'INV', 'series_format' => 'INV-GPC-{YYYY}-{#####}', 'current_number' => 0],
                ['name' => 'proforma_invoice', 'prefix' => 'PF', 'series_format' => 'PF-GPC-{YYYY}-{#####}', 'current_number' => 0],
                ['name' => 'purchase_order', 'prefix' => 'PO', 'series_format' => 'PO-GPC-{YYYY}-{#####}', 'current_number' => 0],
            ] as $ns) {
                NamingSeries::create($ns + ['company_id' => $company->id, 'year' => (int) date('Y')]);
            }

            // ─── Users ─────────────────────────────────────────────────────
            $demoCredentials = [];
            $demoPassword = '123456789';
            $createDemoUser = function (array $attributes, array $roles) use ($company, &$demoCredentials, $demoPassword): User {
                $password = $demoPassword;
                $email = strtolower((string) $attributes['email']);
                $demoCredentials[$email] = $password;

                $user = User::factory()->create($attributes + [
                    'company_id' => $company->id,
                    'password' => Hash::make($password),
                ]);
                $user->syncRoles($roles);

                return $user;
            };

            $superAdmin = $createDemoUser(['name' => 'Setup Administrator', 'email' => 'superadmin@jawla.test', 'employee_code' => 'EMP-000'], ['super_admin', 'hr_admin']);
            $admin = $createDemoUser(['name' => 'عمرو حكيم', 'email' => 'admin@jawla.test', 'employee_code' => 'EMP-001'], ['admin', 'hr_admin']);
            $manager = $createDemoUser(['name' => 'مدير المبيعات', 'email' => 'manager@jawla.test', 'employee_code' => 'EMP-002'], ['sales_manager']);
            $createDemoUser(['name' => 'مالية', 'email' => 'accounts@jawla.test', 'employee_code' => 'EMP-003'], ['accounts', 'system_viewer']);
            $createDemoUser(['name' => 'مشتريات', 'email' => 'purchasing@jawla.test', 'employee_code' => 'EMP-004'], ['purchasing']);
            $warehouseKeeper = $createDemoUser(['name' => 'أمين المستودع', 'email' => 'warehouse@jawla.test', 'employee_code' => 'EMP-005'], ['warehouse_keeper']);
            $createDemoUser(['name' => 'محمد طه', 'email' => 'executive@jawla.test', 'employee_code' => 'EMP-006'], ['executive', 'system_viewer']);
            $rep1 = $createDemoUser(['name' => 'أحمد سعيد', 'email' => 'rep@jawla.test', 'employee_code' => 'EMP-007'], ['rep', 'sales_rep']);
            $rep2 = $createDemoUser(['name' => 'محمد علي', 'email' => 'rep2@jawla.test', 'employee_code' => 'EMP-008'], ['rep', 'sales_rep']);

            Storage::disk('private')->put(
                'demo-credentials.json',
                json_encode($demoCredentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );
            @chmod(Storage::disk('private')->path('demo-credentials.json'), 0600);

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
            $catVirgin = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'بوليمرات (خام)', 'name_en' => 'Virgin Polymers', 'sort_order' => 1]);
            $catRecycled = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'بوليمرات (معاد تدويرها)', 'name_en' => 'Recycled Polymers', 'sort_order' => 2]);
            $catChemicals = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'كيماويات', 'name_en' => 'Chemicals', 'sort_order' => 3]);
            $catPackaging = ProductCategory::create(['company_id' => $company->id, 'name_ar' => 'مواد تغليف', 'name_en' => 'Packaging Materials', 'sort_order' => 4]);

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
            $van1Products = array_filter($products, fn ($p) => in_array($p->sku, ['VIR-PP-H030', 'VIR-PE-HD56S', 'VIR-PE-LD200', 'CHM-CACO3', 'PKG-SHRINK']));
            foreach ($van1Products as $p) {
                $stockService->increment($van1->id, $p->id, null, 10, StockReason::Initial, $van1);
            }

            // Van stock — rep 2
            $van2Products = array_filter($products, fn ($p) => in_array($p->sku, ['VIR-PP-H030', 'VIR-PVC-S65', 'REC-rPP-01', 'CHM-STEARATE', 'PKG-POPP']));
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
            $ws1 = WorkSession::create([
                'company_id' => $company->id,
                'user_id' => $rep1->id,
                'route_id' => $routeCairo->id,
                'started_at' => now()->subHours(2),
                'start_latitude' => 30.0444,
                'start_longitude' => 31.2357,
            ]);

            $ws2 = WorkSession::create([
                'company_id' => $company->id,
                'user_id' => $rep2->id,
                'route_id' => $routeGiza->id,
                'started_at' => now()->subHours(3),
                'start_latitude' => 30.0131,
                'start_longitude' => 31.2089,
            ]);

        } // end if (! $alreadySeeded)

        // Resolve variables for transactional section (may be from DB if base was seeded earlier)
        $products = Product::where('company_id', $company->id)->get();
        $customers = Customer::where('company_id', $company->id)->where('status', 'approved')->get();
        $stockService = app(StockServiceContract::class);
        $rep1 = User::where('email', 'rep@jawla.test')->first();
        $rep2 = User::where('email', 'rep2@jawla.test')->first();
        $admin = User::where('email', 'admin@jawla.test')->first();
        $manager = User::where('email', 'manager@jawla.test')->first();
        $superAdmin = User::where('email', 'superadmin@jawla.test')->first();
        $warehouseKeeper = User::where('email', 'warehouse@jawla.test')->first();
        $mainWarehouse = Warehouse::where('company_id', $company->id)->where('type', 'main')->first();
        $van1 = Warehouse::where('user_id', optional($rep1)->id)->where('type', 'van')->first();
        $van2 = Warehouse::where('user_id', optional($rep2)->id)->where('type', 'van')->first();
        $ws1 = WorkSession::where('user_id', optional($rep1)->id)->first();
        $ws2 = WorkSession::where('user_id', optional($rep2)->id)->first();
        $routeCairo = Route::where('company_id', $company->id)->where('name_ar', 'like', '%القاهرة%')->first();
        $routeGiza = Route::where('company_id', $company->id)->where('name_ar', 'like', '%الجيزة%')->first();
        $routeAlex = Route::where('company_id', $company->id)->where('name_ar', 'like', '%الإسكندرية%')->first();
        $stockQties = [];

        // ═══════════════════════════════════════════════════════════
        //  TRANSACTIONAL DEMO DATA — invoices, payments, POs, etc.
        // ═══════════════════════════════════════════════════════════
        DB::transaction(function () use (
            $company, $products, $customers, $stockService, $stockQties,
            $rep1, $rep2, $van1, $van2, $ws1, $ws2,
            $routeCairo, $routeGiza,
        ) {
            // Skip if transactional data already exists
            if (Invoice::where('company_id', $company->id)->exists()) {
                echo "Transactional data already seeded — skipping.\n";

                return;
            }

            // ─── Suppliers ────────────────────────────────────────
            $suppliers = [];
            $suppliers[] = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-001', 'name_ar' => 'سابك', 'name_en' => 'SABIC', 'type' => 'international', 'contact_person' => 'Ahmed Al-Rashid', 'phone' => '+966500000001', 'email' => 'sales@sabic.com', 'payment_terms' => 'LC 90 days']);
            $suppliers[] = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-002', 'name_ar' => 'بروج', 'name_en' => 'Borouge', 'type' => 'international', 'contact_person' => 'Fatima Al-Mansoori', 'phone' => '+971500000002', 'email' => 'orders@borouge.com', 'payment_terms' => 'LC 60 days']);
            $suppliers[] = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-003', 'name_ar' => 'إكسون موبيل', 'name_en' => 'ExxonMobil Chemical', 'type' => 'international', 'contact_person' => 'John Mitchell', 'phone' => '+12810000003', 'email' => 'polyolefins@exxonmobil.com', 'payment_terms' => 'TT 30 days']);
            $suppliers[] = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-004', 'name_ar' => 'جولدن باور للكيماويات', 'name_en' => 'Golden Power Chemicals', 'type' => 'local', 'contact_person' => 'Hassan Ibrahim', 'phone' => '01011223344', 'email' => 'hassan@gpc-eg.com', 'payment_terms' => 'Net 30']);
            $supplierMap = [];
            foreach ($suppliers as $s) {
                $supplierMap[$s->code] = $s;
            }

            $names = app(NumberSequenceService::class);

            // ─── Purchase Orders ──────────────────────────────────
            $pos = [];
            $poDefs = [
                ['supplier' => 'SUP-001', 'status' => 'confirmed', 'days' => 55, 'items' => [['sku' => 'VIR-PP-H030', 'qty' => 50, 'price' => 38000], ['sku' => 'VIR-PP-H530', 'qty' => 30, 'price' => 38500]]],
                ['supplier' => 'SUP-001', 'status' => 'received', 'days' => 45, 'items' => [['sku' => 'VIR-PE-HD56S', 'qty' => 40, 'price' => 41000]]],
                ['supplier' => 'SUP-002', 'status' => 'partial', 'days' => 30, 'items' => [['sku' => 'VIR-PE-HD6760', 'qty' => 25, 'price' => 42000], ['sku' => 'VIR-PET-REG', 'qty' => 15, 'price' => 36500]]],
                ['supplier' => 'SUP-003', 'status' => 'draft', 'days' => 15, 'items' => [['sku' => 'VIR-PE-LD200', 'qty' => 35, 'price' => 39500], ['sku' => 'VIR-PS-GPPS', 'qty' => 20, 'price' => 35500]]],
                ['supplier' => 'SUP-003', 'status' => 'confirmed', 'days' => 10, 'items' => [['sku' => 'VIR-PP-H030', 'qty' => 60, 'price' => 37800]]],
                ['supplier' => 'SUP-004', 'status' => 'received', 'days' => 20, 'items' => [['sku' => 'CHM-CACO3', 'qty' => 100, 'price' => 6200], ['sku' => 'CHM-STEARATE', 'qty' => 200, 'price' => 95]]],
            ];
            foreach ($poDefs as $poDef) {
                $orderDate = today()->subDays($poDef['days']);
                $items = [];
                $subtotal = 0;
                foreach ($poDef['items'] as $it) {
                    $prod = collect($products)->firstWhere('sku', $it['sku']);
                    if (! $prod) {
                        continue;
                    }
                    $lineTotal = $it['qty'] * $it['price'];
                    $subtotal += $lineTotal;
                    $items[] = ['product' => $prod, 'qty' => $it['qty'], 'price' => $it['price'], 'line' => $lineTotal];
                }
                if (empty($items)) {
                    continue;
                }
                $po = PurchaseOrder::create([
                    'company_id' => $company->id,
                    'supplier_id' => $supplierMap[$poDef['supplier']]->id,
                    'order_number' => $names->generate('purchase_order', $company->id),
                    'status' => $poDef['status'],
                    'order_date' => $orderDate,
                    'expected_delivery_date' => $orderDate->copy()->addDays(30),
                    'currency' => 'EGP',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $subtotal * 0.05,
                    'total' => $subtotal * 1.05,
                ]);
                foreach ($items as $it) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $it['product']->id,
                        'quantity' => $it['qty'],
                        'unit_price' => $it['price'],
                        'line_total' => $it['line'],
                    ]);
                }
                $pos[] = $po;
            }

            // ─── Purchase Requests ────────────────────────────────
            $reqStatuses = ['pending', 'pending', 'pending', 'sales_approved', 'sales_approved', 'purchasing_approved', 'rejected_by_sales', 'purchasing_approved', 'pending', 'pending'];
            for ($i = 0; $i < 10; $i++) {
                $prod = $products->random();
                PurchaseRequest::create([
                    'company_id' => $company->id,
                    'user_id' => $i % 2 === 0 ? $rep1->id : $rep2->id,
                    'product_id' => $prod->id,
                    'quantity' => rand(5, 40),
                    'offered_price' => $prod->cost * (rand(90, 110) / 100),
                    'currency' => 'EGP',
                    'status' => $reqStatuses[$i],
                ]);
            }

            // ─── Invoices ─────────────────────────────────────────
            $invoices = [];
            $invoiceCount = 40;
            $skus = array_keys($stockQties);
            for ($i = 0; $i < $invoiceCount; $i++) {
                $rep = $i % 3 === 0 ? $rep2 : $rep1;
                $van = $rep->id === $rep1->id ? $van1 : $van2;
                $daysAgo = rand(0, 85);
                if ($daysAgo < 3) {
                    $daysAgo = rand(0, 3);
                } // bias toward recent
                $issueDate = today()->subDays($daysAgo);
                $cust = $customers->random();

                $itemCount = rand(1, 3);
                $items = [];
                $subtotal = 0;
                for ($j = 0; $j < $itemCount; $j++) {
                    $prod = $products->random();
                    $qty = rand(1, 8);
                    $price = $prod->price;
                    $lineTotal = $qty * $price;
                    $subtotal += $lineTotal;
                    $items[] = ['product' => $prod, 'qty' => $qty, 'price' => $price, 'line' => $lineTotal];
                }
                $vat = round($subtotal * 0.14, 2);
                $total = $subtotal + $vat;

                $invStatus = 'submitted';
                $rand = rand(1, 10);
                if ($rand <= 2) {
                    $invStatus = 'cancelled';
                } elseif ($rand <= 6) {
                    $invStatus = 'paid';
                }

                $inv = Invoice::create([
                    'uuid' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'customer_id' => $cust->id,
                    'user_id' => $rep->id,
                    'invoice_number' => $names->generate('sales_invoice', $company->id),
                    'status' => $invStatus,
                    'subtotal' => $subtotal,
                    'vat_amount' => $vat,
                    'total' => $total,
                    'paid_amount' => $invStatus === 'paid' ? $total : 0,
                    'remaining_amount' => $invStatus === 'paid' ? 0 : $total,
                    'posting_date' => $issueDate,
                    'issued_at' => $issueDate->setTime(9 + rand(0, 8), rand(0, 59)),
                    'cancelled_at' => $invStatus === 'cancelled' ? $issueDate->copy()->addDays(1) : null,
                ]);
                foreach ($items as $it) {
                    InvoiceItem::create([
                        'invoice_id' => $inv->id,
                        'product_id' => $it['product']->id,
                        'quantity' => $it['qty'],
                        'unit_price' => $it['price'],
                        'line_total' => $it['line'],
                    ]);
                }
                // Deduct stock for non-cancelled invoices
                if ($invStatus !== 'cancelled') {
                    foreach ($items as $it) {
                        try {
                            $stockService->decrement($van->id, $it['product']->id, null, $it['qty'], StockReason::Sale, $inv);
                        } catch (\Throwable) {
                        }
                    }
                }
                $invoices[] = $inv;
            }

            // Restock each rep's van after the randomised historical sales above,
            // which can otherwise leave a product's van stock near zero. Reps
            // restock their van each period, so add a healthy buffer — this keeps
            // the demo realistic and makes stock-dependent tests deterministic
            // (the van baseline is always ample) without per-test top-ups.
            $van1Skus = ['VIR-PP-H030', 'VIR-PE-HD56S', 'VIR-PE-LD200', 'CHM-CACO3', 'PKG-SHRINK'];
            $van2Skus = ['VIR-PP-H030', 'VIR-PVC-S65', 'REC-rPP-01', 'CHM-STEARATE', 'PKG-POPP'];
            foreach ([[$van1, $van1Skus], [$van2, $van2Skus]] as [$repVan, $vanSkus]) {
                if (! $repVan) {
                    continue;
                }
                foreach ($products->whereIn('sku', $vanSkus) as $repVanProduct) {
                    $stockService->increment($repVan->id, $repVanProduct->id, null, 150, StockReason::Initial, $repVan);
                }
            }

            // ─── Payments ─────────────────────────────────────────
            $paymentMethods = ['cash', 'cheque', 'transfer'];
            foreach ($invoices as $inv) {
                if ($inv->status === 'cancelled') {
                    continue;
                }
                $shouldPay = rand(1, 10) <= 8;
                if (! $shouldPay) {
                    continue;
                }
                $amount = rand(1, 10) <= 6 ? $inv->total : round($inv->total * (rand(3, 8) / 10), 2);
                $method = $paymentMethods[array_rand($paymentMethods)];
                $payment = Payment::create([
                    'company_id' => $company->id,
                    'customer_id' => $inv->customer_id,
                    'user_id' => $inv->user_id,
                    'invoice_id' => $inv->id,
                    'amount' => $amount,
                    'method' => $method,
                    'collected_at' => $inv->issued_at->copy()->addDays(rand(0, 15)),
                    'posting_date' => $inv->issued_at,
                ]);
                $inv->update([
                    'paid_amount' => $inv->paid_amount + $amount,
                    'remaining_amount' => $inv->remaining_amount - $amount,
                ]);
                if ($inv->remaining_amount <= 0) {
                    $inv->update(['status' => 'paid']);
                } elseif ($inv->paid_amount > 0 && $inv->status !== 'paid') {
                    $inv->update(['status' => 'partially_paid']);
                }
            }

            // ─── Returns ──────────────────────────────────────────
            $returnCount = 0;
            foreach (collect($invoices)->where('status', 'paid')->random(min(4, count($invoices))) as $inv) {
                $returnCount++;
                $item = $inv->items->first();
                if (! $item) {
                    continue;
                }
                $ret = ReturnRecord::create([
                    'company_id' => $company->id,
                    'customer_id' => $inv->customer_id,
                    'user_id' => $inv->user_id,
                    'against_invoice_id' => $inv->id,
                    'return_number' => 'RET-'.now()->format('Ymd').'-'.str_pad($returnCount, 3, '0', STR_PAD_LEFT),
                    'total' => $item->line_total,
                    'reason' => 'منتج تالف / Damaged product',
                    'status' => 'submitted',
                    'returned_at' => $inv->issued_at->copy()->addDays(rand(2, 10)),
                    'posting_date' => $inv->issued_at->copy()->addDays(rand(2, 10)),
                ]);
                ReturnItem::create([
                    'return_id' => $ret->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
                $van = Warehouse::where('user_id', $inv->user_id)->where('type', 'van')->first();
                if ($van) {
                    $stockService->increment($van->id, $item->product_id, null, $item->quantity, StockReason::Return, $ret);
                }
            }

            // ─── Visits ───────────────────────────────────────────
            foreach ($customers as $i => $cust) {
                if ($i >= 15) {
                    break;
                }
                $rep = $cust->route_id === $routeCairo->id ? $rep1 : ($cust->route_id === $routeGiza->id ? $rep2 : $rep1);
                $ws = $rep->id === $rep1->id ? $ws1 : $ws2;
                $daysAgo = rand(0, 7);
                $checkin = today()->subDays($daysAgo)->setTime(9 + rand(0, 5), rand(0, 59));
                Visit::create([
                    'user_id' => $rep->id,
                    'customer_id' => $cust->id,
                    'route_id' => $cust->route_id,
                    'work_session_id' => $ws->id,
                    'purpose' => 'sale',
                    'status' => 'closed',
                    'checkin_latitude' => $cust->latitude,
                    'checkin_longitude' => $cust->longitude,
                    'checkin_at' => $checkin,
                    'checkout_at' => $checkin->copy()->addMinutes(rand(20, 90)),
                ]);
            }

            // ─── Expenses ─────────────────────────────────────────
            $expenseCategories = ['fuel', 'food', 'maintenance', 'food', 'fuel', 'food', 'maintenance', 'fuel', 'other', 'fuel', 'food', 'food', 'fuel'];
            foreach ($expenseCategories as $cat) {
                $rep = rand(0, 1) ? $rep1 : $rep2;
                $amounts = ['fuel' => [200, 500], 'food' => [50, 150], 'maintenance' => [300, 1500], 'other' => [50, 300]];
                Expense::create([
                    'company_id' => $company->id,
                    'user_id' => $rep->id,
                    'category' => $cat,
                    'amount' => rand($amounts[$cat][0], $amounts[$cat][1]),
                    'note' => $cat === 'fuel' ? 'وقود' : ($cat === 'food' ? 'غداء' : 'صيانة'),
                    'spent_at' => today()->subDays(rand(0, 25)),
                    'posting_date' => today()->subDays(rand(0, 25)),
                ]);
            }

            // ─── Alarms ───────────────────────────────────────────
            $alarmDefs = [
                ['type' => 'out_of_stock_request', 'title' => 'طلب مخزون - بولي كربونات', 'severity' => 'warning'],
                ['type' => 'out_of_stock_request', 'title' => 'طلب مخزون - ثاني أكسيد التيتانيوم', 'severity' => 'warning'],
                ['type' => 'customer_complaint', 'title' => 'شكوى عميل - تأخر تسليم', 'severity' => 'critical'],
                ['type' => 'customer_complaint', 'title' => 'شكوى - جودة منتج REC-rPVC', 'severity' => 'warning'],
                ['type' => 'batch_expiring', 'title' => 'دفعة أوشكت على الانتهاء - r-PET', 'severity' => 'info'],
                ['type' => 'purchase_request', 'title' => 'طلب شراء جديد - PP H030', 'severity' => 'info'],
                ['type' => 'purchase_request', 'title' => 'طلب شراء جديد - PE-LD200', 'severity' => 'info'],
                ['type' => 'goods_in_transit_delayed', 'title' => 'تأخير شحنة - SABIC PP', 'severity' => 'warning'],
            ];
            foreach ($alarmDefs as $ad) {
                Alarm::create([
                    'company_id' => $company->id,
                    'type' => $ad['type'],
                    'title' => $ad['title'],
                    'description' => $ad['title'],
                    'severity' => $ad['severity'],
                    'is_read' => rand(0, 1),
                ]);
            }

            // ─── Price Quotation Requests ─────────────────────────
            $quoteStatuses = ['requested', 'requested', 'priced', 'priced', 'confirmed', 'requested', 'requested'];
            foreach ($quoteStatuses as $i => $status) {
                $prod = $products->random();
                $cust = $customers->random();
                PriceQuotationRequest::create([
                    'company_id' => $company->id,
                    'customer_id' => $cust->id,
                    'user_id' => $i % 2 === 0 ? $rep1->id : $rep2->id,
                    'product_id' => $prod->id,
                    'quantity_requested' => rand(5, 50),
                    'status' => $status,
                    'requested_at' => now()->subDays(rand(0, 14)),
                ]);
            }

            echo '  Invoices: '.count($invoices)." (mix submitted/paid/cancelled)\n";
            echo '  Payments: '.Payment::count()."\n";
            echo '  Purchase Orders: '.count($pos)."\n";
            echo '  Suppliers: '.count($suppliers)."\n";
            echo '  Alarms: '.Alarm::count()."\n";
            echo '  Returns: '.$returnCount."\n";
            echo '  Expenses: '.count($expenseCategories)."\n";
            echo '  Visits: '.Visit::count()."\n";
        });

        echo "GPC demo seeded:\n";
        echo "  Company: شركة اللدائن العالمية (GPC)\n";
        echo '  Products: '.count($products)." across 4 categories\n";
        echo '  Customers: '.count($customers)." across 3 routes\n";
        echo "  Credentials: storage/app/private/demo-credentials.json (mode 0600)\n";
    }
}
