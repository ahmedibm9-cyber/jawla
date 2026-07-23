# Jawla (جولة) — Internal Service & Component Contracts

> Jawla is a Laravel + Livewire application (not a REST API). This document defines the **service layer contracts** and **Livewire component interfaces** that form the system's internal API. These are the contracts the AI must implement.

---

## Part I — Service Layer (app/Services/)

### SVC-01: StockService

```php
interface StockServiceInterface
{
    /**
     * Add stock to a warehouse.
     * @param int $warehouseId
     * @param int $productId
     * @param float $quantity  Positive value
     * @param int|null $batchId
     * @param float|null $valuationRate  Cost at time of addition
     * @param string $reason  From stock_movements.reason enum
     * @param string $referenceType  Morph type (e.g., 'purchase_order')
     * @param int $referenceId  Morph ID
     * @param int $userId
     * @param string $postingDate  Y-m-d
     * @throws \InvalidArgumentException
     */
    public function addStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $batchId,
        ?float $valuationRate,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $userId,
        string $postingDate
    ): StockMovement;

    /**
     * Remove stock from a warehouse.
     * @throws InsufficientStockException if quantity would go negative
     */
    public function removeStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $batchId,
        ?float $valuationRate,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $userId,
        string $postingDate
    ): StockMovement;

    /**
     * Transfer stock between warehouses (e.g., main → van, van-to-van).
     * Creates two stock_movements: one -qty (source), one +qty (destination).
     */
    public function transferStock(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        float $quantity,
        ?int $batchId,
        int $userId,
        string $postingDate
    ): array;  // [outMovement, inMovement]

    /**
     * Get available quantity of a product across all warehouses (optionally filtered).
     */
    public function getAvailableQty(
        int $productId,
        ?int $warehouseId = null,
        ?int $batchId = null
    ): float;

    /**
     * Get batch-level stock breakdown for a product.
     * @return array<{batch_id: int|null, batch_number: string|null, warehouse_id: int, warehouse_name: string, quantity: float, expiry_date: string|null}>
     */
    public function getBatchStock(int $productId, ?int $warehouseId = null): array;

    /**
     * Distribute landed costs across GIT or PO items proportionally by quantity.
     * Updates product cost price using moving average.
     */
    public function distributeLandedCost(
        int $goodsInTransitId,
        ?int $purchaseOrderId
    ): void;
}
```

### SVC-02: NamingSeriesService

```php
interface NamingSeriesServiceInterface
{
    /**
     * Generate the next document number for a given document type and company.
     * Follows format: PREFIX-{COMPANY_ABBR}-{YYYY}-{#####}
     * Reads current_number from naming_series, increments atomically.
     *
     * @param string $documentType  e.g., 'invoice', 'proforma', 'purchase_order', 'return', 'goods_in_transit'
     * @param int $companyId
     * @return string  e.g., 'INV-GPC-2026-00042'
     */
    public function generate(string $documentType, int $companyId): string;
}
```

### SVC-03: InvoiceService

```php
interface InvoiceServiceInterface
{
    /**
     * Create an invoice with items, deduct stock, and generate PDF.
     * Wrapped in a single DB::transaction(). Rolls back on any failure.
     *
     * @param array{company_id: int, customer_id: int, user_id: int, visit_id: int|null, proforma_invoice_id: int|null, items: array<{product_id: int, batch_id: int|null, quantity: float, unit_price: float}>, posting_date: string}
     * @return Invoice
     * @throws InsufficientStockException
     * @throws PriceOutOfRangeException
     * @throws CustomerPendingException
     * @throws BatchRequiredException
     */
    public function createInvoice(array $data): Invoice;

    /**
     * Cancel a submitted invoice. Reverses stock movements.
     * Only if invoice has no linked payments.
     */
    public function cancelInvoice(int $invoiceId, int $userId): Invoice;

    /**
     * Generate bilingual AR/EN PDF for an invoice.
     * Includes Egypt ETA QR code.
     * @return string  Path to generated PDF file
     */
    public function generatePdf(int $invoiceId): string;

    /**
     * Generate Egypt ETA QR code content.
     * @return string  Base64-encoded QR image
     */
    public function generateEtaQr(Invoice $invoice): string;
}
```

### SVC-04: PriceService

```php
interface PriceServiceInterface
{
    /**
     * Create a price quotation request from a rep.
     */
    public function requestQuotation(int $companyId, int $customerId, int $userId, int $productId, float $quantity, ?int $visitId): PriceQuotationRequest;

    /**
     * Manager prices a quotation request.
     * Validates rep_plus ≤ manager_plus and rep_minus ≤ manager_minus.
     */
    public function priceQuotation(int $requestId, int $managerId, float $basePrice, float $managerPlus, float $managerMinus, float $repPlus, float $repMinus): PriceQuotation;

    /**
     * Rep negotiates final price within their allowed range.
     * Validates: basePrice - repMinus ≤ finalPrice ≤ basePrice + repPlus
     * @throws PriceOutOfRangeException
     */
    public function confirmPrice(int $quotationId, float $finalPrice): PriceQuotation;

    /**
     * Validate that a given unit price is within the rep's allowed range
     * for a given product and customer.
     */
    public function validatePriceWithinRange(int $productId, int $customerId, int $repId, float $unitPrice): bool;
}
```

### SVC-05: VisitService

```php
interface VisitServiceInterface
{
    /**
     * Start a work session for a rep (check-in).
     * Captures current GPS coordinates.
     */
    public function startWorkSession(int $userId, float $startLat, float $startLng): WorkSession;

    /**
     * End a work session (check-out).
     * Returns daily summary stats.
     * @return array{visits_completed: int, visits_total: int, total_sales: float, total_collections: float, total_returns: float, total_expenses: float, cash_box_balance: float}
     */
    public function endWorkSession(int $workSessionId): array;

    /**
     * Start a visit. Returns geofence check result.
     * @return array{within_geofence: bool, distance_meters: float, visit: Visit}
     */
    public function startVisit(int $assignmentId, int $userId, float $checkinLat, float $checkinLng): array;

    /**
     * Submit structured visit report.
     */
    public function submitReport(int $visitId, string $summary, ?string $feedback, ?string $actionTaken, bool $followUpNeeded, ?string $followUpNote): VisitReport;

    /**
     * End a visit (check-out).
     */
    public function endVisit(int $visitId): Visit;

    /**
     * Calculate haversine distance between two GPS coordinates.
     * @return float  Distance in meters
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float;
}
```

### SVC-06: CashBoxService

```php
interface CashBoxServiceInterface
{
    /**
     * Get current balance for a rep's cash box.
     */
    public function getBalance(int $userId): float;

    /**
     * Add funds to cash box (collection).
     */
    public function deposit(int $userId, float $amount, int $paymentId): CashBox;

    /**
     * Remove funds from cash box (expense, return payout).
     * @throws InsufficientFundsException
     */
    public function withdraw(int $userId, float $amount, ?string $reason, ?int $referenceId): CashBox;
}
```

### SVC-07: AlarmService

```php
interface AlarmServiceInterface
{
    /**
     * Create an alarm from a trigger event.
     * @param string $type  One of the 7 alarm types
     * @param string $title
     * @param string $description
     * @param string $severity  info|warning|critical
     * @param Model $reference  The model that triggered the alarm (polymorphic)
     */
    public function create(string $type, string $title, string $description, string $severity, Model $reference): Alarm;

    /**
     * Auto-generate alarms for all 7 trigger types.
     * Called from observers/listeners on trigger models.
     */
    public function autoGenerate(string $triggerType, Model $sourceModel): ?Alarm;

    /**
     * Batch expiry check — called by scheduled task daily.
     * Creates warning alarms for batches expiring within 30 days.
     */
    public function checkExpiringBatches(): int;  // count created

    /**
     * GIT delay check — called by scheduled task daily.
     * Creates critical alarms for shipments past ETA.
     */
    public function checkDelayedShipments(): int;  // count created
}
```

---

## Part II — Livewire Components (app/Http/Livewire/)

### LWC-01: HomePage

```
Path:        /app
Component:   App\Http\Livewire\HomePage
Layout:      components.layouts.app
Permissions: auth, role:rep

Properties:
  - $workSession: ?WorkSession  (nullable — active or none)
  - $visitsCount: int
  - $pendingQuotationsCount: int
  - $pendingCustomersCount: int
  - $cashBoxBalance: float
  - $alarmsCount: int  (unread only)

Methods:
  - startWork(): void  — GPS prompt → create WorkSession → redirect to /app/visits
  - endWork(): void  — close session → show summary modal
  - mount(): void  — load all dashboard data
```

### LWC-02: VisitList

```
Path:        /app/visits
Component:   App\Http\Livewire\VisitList
Permissions: auth, role:rep

Properties:
  - $visits: Collection  (today's assigned visits with customer info)
  - $workSessionId: int

Methods:
  - mount(): void  — load today's daily_visit_assignments for this user ordered by sort_order
  - startVisit(int $assignmentId): void  — GPS check → redirect to /app/visits/{visit}
```

### LWC-03: VisitPage

```
Path:        /app/visits/{visit}
Component:   App\Http\Livewire\VisitPage
Permissions: auth, role:rep

Properties:
  - $visit: Visit
  - $withinGeofence: bool
  - $distance: float
  - $reportSummary: string
  - $reportFeedback: string
  - $reportActionTaken: string
  - $followUpNeeded: bool
  - $followUpNote: string

Methods:
  - confirmArrival(): void  — manual override if outside geofence
  - submitReport(): void  — validates + saves visit_report
  - endVisit(): void  — sets checkout_at → back to /app/visits
```

### LWC-04: CreateCustomer

```
Path:        /app/customers/create
Component:   App\Http\Livewire\CreateCustomer
Permissions: auth, role:rep

Properties:
  - $nameAr: string
  - $nameEn: string
  - $phone: string
  - $address: string
  - $latitude: float
  - $longitude: float
  - $notes: string

Methods:
  - mount(): void  — capture GPS automatically via browser geolocation
  - save(): void  — validate → create customer with status=pending → create alarm → redirect
  - Real-time validation on phone (unique check via debounce)
```

### LWC-05: InvoiceCreate

```
Path:        /app/visits/{visit}/invoice
Component:   App\Http\Livewire\InvoiceCreate
Permissions: auth, role:rep

Properties:
  - $visit: Visit
  - $customer: Customer
  - $items: array<{product_id, batch_id, quantity, unit_price, line_total, product_name}>
  - $availableStock: Collection  (van stock + main warehouse + GIT)
  - $subtotal: float
  - $vatAmount: float
  - $total: float

Rules:
  - Each item.quantity must be ≤ available van stock
  - Each item.unit_price must be within rep's price range
  - Batch required if product.track_batch=true

Methods:
  - addItem(): void  — add row to items table
  - removeItem(int $index): void
  - updateLineTotal(int $index): void  — recalculates on qty/price change
  - recalculateTotals(): void
  - submit(): void  — calls InvoiceService::createInvoice → redirect to invoice view
```

### LWC-06: PaymentCreate

```
Path:        /app/visits/{visit}/payment
Component:   App\Http\Livewire\PaymentCreate
Permissions: auth, role:rep

Properties:
  - $visit: Visit
  - $customer: Customer
  - $amount: float
  - $modeOfPaymentId: int
  - $invoiceId: ?int  (optional link to invoice)
  - $notes: string

Methods:
  - mount(): void  — load modes_of_payment
  - save(): void  — create payment → deposit to cash box → update customer balance → update invoice paid_amount
```

### LWC-07: ReturnCreate

```
Path:        /app/visits/{visit}/return
Component:   App\Http\Livewire\ReturnCreate
Permissions: auth, role:rep

Properties:
  - $visit: Visit
  - $items: array<{product_id, batch_id, quantity, unit_price}>
  - $reason: string

Methods:
  - addItem(): void
  - submit(): void  — create return → add stock back to van → update customer balance
```

### LWC-08: QuotationRequest

```
Path:        /app/visits/{visit}/quotation
Component:   App\Http\Livewire\QuotationRequest
Permissions: auth, role:rep

Properties:
  - $visit: Visit
  - $productId: int
  - $quantity: float
  - $statusText: string  (read-only feedback on status)

Methods:
  - submit(): void  — create price_quotation_request → create alarm
  - mount(): void  — if existing request exists, show current status
```

### LWC-09: ProformaCreate

```
Path:        /app/visits/{visit}/proforma
Component:   App\Http\Livewire\ProformaCreate
Permissions: auth, role:rep

Properties:
  - $visit: Visit
  - $customer: Customer
  - $quotationId: ?int
  - $items: array<{product_id, quantity, unit_price, line_total}>
  - $subtotal, $vatAmount, $total: float
  - $bankAccount: CompanyBankAccount

Methods:
  - mount(): void  — if coming from quotation, pre-fill items + validate prices
  - addItem(): void
  - submit(): void  — creates proforma, validates prices against rep's range
```

### LWC-10: StockSearch

```
Path:        /app/stock  (or embedded in InvoiceCreate)
Component:   App\Http\Livewire\StockSearch
Permissions: auth, role:rep

Properties:
  - $query: string
  - $results: Collection  (products with stock per warehouse + GIT)

Methods:
  - updatedQuery(): void  — debounced search (300ms)
  - mount(): void  — initial load with empty results
```

---

## Part III — Scheduled Tasks (app/Console/Kernel.php)

| Signature                       | Schedule         | Description                                                                         |
| ------------------------------- | ---------------- | ----------------------------------------------------------------------------------- |
| `alarms:check-expiring-batches` | Daily 02:00      | Scan batches expiry ≤ 30d → create warning alarms                                   |
| `alarms:check-delayed-git`      | Hourly           | Scan GIT where status≠received AND estimated_arrival < now → create critical alarms |
| `visits:auto-miss`              | Daily 23:30      | Mark pending daily_visit_assignments for today as 'missed'                          |
| `stock:generate-reorder-report` | Weekly Sun 06:00 | Email low-stock report to warehouse keeper                                          |

---

## Part IV — Observers (app/Observers/)

| Observer                 | Model                 | Event                       | Action                                                   |
| ------------------------ | --------------------- | --------------------------- | -------------------------------------------------------- |
| OutOfStockObserver       | OutOfStockRequest     | created                     | AlarmService::autoGenerate('out_of_stock_request')       |
| ComplaintObserver        | Complaint             | created                     | AlarmService::autoGenerate('customer_complaint')         |
| CustomerObserver         | Customer              | created (if status=pending) | AlarmService::autoGenerate('new_customer_pending')       |
| QuotationRequestObserver | PriceQuotationRequest | created                     | AlarmService::autoGenerate('price_quotation_requested')  |
| PurchaseRequestObserver  | PurchaseRequest       | created                     | AlarmService::autoGenerate('purchase_request_submitted') |

---

## Part V — Filament Resources (app/Filament/Resources/)

| Resource                  | Model                 | Key Features                                                |
| ------------------------- | --------------------- | ----------------------------------------------------------- |
| CompanyResource           | Company               | CRUD, read-only after creation safeguard                    |
| UserResource              | User                  | CRUD with role assignment, auto-create van+cash on rep      |
| ProductCategoryResource   | ProductCategory       | CRUD with sort_order                                        |
| ProductResource           | Product               | CRUD, cost field hidden from sales roles via `hidden(fn())` |
| BatchResource             | Batch                 | CRUD, COA PDF upload, product+batch stock view              |
| SupplierResource          | Supplier              | CRUD with local/international toggle                        |
| RouteResource             | Route                 | CRUD with user assignment (BelongsToMany)                   |
| CustomerResource          | Customer              | CRUD, Leaflet location picker, approve/reject actions       |
| WarehouseResource         | Warehouse             | CRUD, stock view per warehouse, CSV import action           |
| GoodsInTransitResource    | GoodsInTransit        | CRUD, status progression, landed cost management            |
| InvoiceResource           | Invoice               | List/filter, PDF download action, cancel action             |
| PaymentResource           | Payment               | List/filter with mode of payment breakdown                  |
| QuotationRequestResource  | PriceQuotationRequest | Manager pricing workflow (custom form)                      |
| PurchaseOrderResource     | PurchaseOrder         | CRUD, partial receipt tracking                              |
| SupplierQuotationResource | SupplierQuotation     | Side-by-side comparison (custom view)                       |
| AlarmResource             | Alarm                 | Grouped by severity, acknowledge/assign/resolve actions     |
| ComplaintResource         | Complaint             | Lifecycle management, assignment                            |
| ReportPage                | —                     | Dashboard widgets, charts, Excel export                     |

---

## Part VI — Key Laravel Events (app/Events/)

| Event              | Dispatched By         | Listeners                                                |
| ------------------ | --------------------- | -------------------------------------------------------- |
| `InvoiceCreated`   | InvoiceService        | Generate PDF, Send notification                          |
| `InvoiceCancelled` | InvoiceService        | Reverse stock movements                                  |
| `PaymentCollected` | PaymentService        | Update cash box, Update customer balance                 |
| `GoodsReceived`    | GoodsInTransitService | Distribute landed costs, Update stock, Update cost price |
| `AlarmCreated`     | AlarmService          | Broadcast notification (if Reverb configured)            |
| `CustomerApproved` | CustomerService       | Notify rep                                               |
| `PriceQuoted`      | PriceService          | Notify rep                                               |
| `VisitCompleted`   | VisitService          | Update daily_visit_assignment status                     |

---

## Part VII — Form Requests (app/Http/Requests/)

| Request                 | Validates        | Rules                                                            |
| ----------------------- | ---------------- | ---------------------------------------------------------------- |
| `StoreInvoiceRequest`   | Invoice creation | stock ≥ qty, price in range, customer approved, batch if tracked |
| `StorePaymentRequest`   | Payment          | amount > 0, valid mode_of_payment                                |
| `PriceQuotationRequest` | Manager pricing  | rep_plus ≤ manager_plus, rep_minus ≤ manager_minus               |
| `CustomerStoreRequest`  | New customer     | phone unique per company, name_ar required                       |
| `GoodsReceiveRequest`   | GIT receipt      | valid status transition, all items have batches if tracked       |
