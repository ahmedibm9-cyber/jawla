# Jawla (جولة) — UI Specification

## 1. Design System

### 1.1 Colors

| Token         | Hex       | Usage                                               |
| ------------- | --------- | --------------------------------------------------- |
| primary-500   | `#4DB848` | Primary buttons, links, active states, header brand |
| primary-600   | `#3DA038` | Hover states                                        |
| primary-700   | `#2D8828` | Active/pressed                                      |
| secondary-500 | `#2C6FB4` | Secondary buttons, info banners, accent elements    |
| secondary-600 | `#1C5FA4` | Hover states                                        |
| danger-500    | `#DC2626` | Delete, cancel, critical alarm (red)                |
| warning-500   | `#F59E0B` | Warning alarms, pending indicators                  |
| info-500      | `#3B82F6` | Info alarms                                         |
| gray-50       | `#F9FAFB` | Page background                                     |
| gray-100      | `#F3F4F6` | Card background                                     |
| gray-700      | `#374151` | Body text                                           |
| gray-900      | `#111827` | Headings                                            |

### 1.2 Typography

| Element          | Font                                   | Weight     | Size     |
| ---------------- | -------------------------------------- | ---------- | -------- |
| Arabic headings  | Noto Kufi Arabic                       | 700 (Bold) | 1.25rem+ |
| English headings | Montserrat                             | 700 (Bold) | 1.25rem+ |
| Body text        | Open Sans (EN) / Noto Kufi Arabic (AR) | 400        | 0.875rem |
| Small/caption    | Open Sans / Noto Kufi Arabic           | 400        | 0.75rem  |

### 1.3 Direction & Localization

- Default: `dir="rtl"`, `lang="ar"`
- Language switcher flips `dir` attribute + loads opposite font stack
- All form labels, navigation, errors bilingual AR first, EN second
- Numbers always displayed in Hindu-Arabic numerals (0-9)

### 1.4 Spacing

- Card padding: `p-4` (16px)
- Between cards: `gap-4`
- Section margins: `mb-6`
- Touch targets: minimum `h-11` (44px) for all interactive elements

### 1.5 Dark Mode

- Filament built-in dark mode enabled
- Rep PWA: dark mode toggle in header, respects `prefers-color-scheme`
- Dark mode = gray-900 backgrounds, gray-100 text

---

## 2. Admin Panel (Filament — `/admin`)

### 2.1 Layout

- **Top bar:** Company logo (left in RTL), language switcher, dark mode toggle, user avatar + name, notification bell with badge
- **Sidebar:** Role-filtered navigation (collapsible, icons + labels)
- **Main content:** Cards, tables, forms per Filament conventions

### 2.2 Navigation Structure (Admin / Full Access)

```
📊 Dashboard
📦 Master Data
  ├── Companies
  ├── Users & Roles
  ├── Products
  │   ├── Categories
  │   └── Products
  ├── Batches & COA
  ├── Suppliers
  ├── Routes
  └── Customers
🏬 Warehouse
  ├── Warehouses
  ├── Stock View
  ├── Stock Import
  └── Stock Adjustments
🚢 Goods in Transit
  ├── GIT Shipments
  └── Landed Costs
💰 Sales
  ├── Price Quotations
  ├── Proforma Invoices
  ├── Invoices
  └── Returns
💳 Financial
  ├── Payments
  ├── Modes of Payment
  └── Cash Boxes
📋 Purchasing
  ├── Purchase Requests
  ├── Supplier Quotations
  ├── Purchase Orders
  └── Goods Receipt
🔔 Alarms
  ├── Critical
  ├── Warning
  └── Info
📞 CRM
  ├── Complaints
  └── OOS Requests
📈 Reports
  ├── Sales Report
  ├── Visit Report
  ├── Stock Report
  ├── Alarm Report
  ├── GST Report
  └── Executive Dashboard
🔄 Data Migration
  ├── Import Customers
  ├── Import Suppliers
  ├── Import Products
  ├── Import Stock
  └── Import Invoices
⚙️ Settings
  ├── Naming Series
  ├── Tax Templates
  ├── Company Bank Accounts
  ├── Customer Groups
  ├── Territories
  └── Price Lists
```

### 2.3 Key Page Prototypes

#### 2.3.1 Dashboard

```
┌──────────────────────────────────────────────────────┐
│ 📊 Dashboard                                         │
├──────────────────────────────────────────────────────┤
│ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌─────┐│
│ │Sales Today │ │Visits Today│ │Active      │ │Stock│ │
│ │EGP 142,500 │ │12/15       │ │Alarms 3    │ │Value│ │
│ │+8% vs yesterday         │ │80% compliance│ │ │ │$2.1M│ │
│ └────────────┘ └────────────┘ └────────────┘ └─────┘│
│                                                      │
│ ┌────────────────────┐ ┌────────────────────────────┐│
│ │ Weekly Sales Trend │ │ Top Products               ││
│ │ [bar chart]        │ │ 1. HDPE 512   EGP 420,000 ││
│ │                    │ │ 2. PP 240     EGP 310,000 ││
│ │                    │ │ 3. PVC 180    EGP 280,000 ││
│ └────────────────────┘ └────────────────────────────┘│
│                                                      │
│ ┌────────────────────┐ ┌────────────────────────────┐│
│ │ Visit Map [Leaflet]│ │ Recent Alarms              ││
│ │ GPS pins colored   │ │ 🔴 OOS: PP 512 - 2min ago ││
│ │ by status          │ │ 🟡 New customer - 15min ago││
│ └────────────────────┘ └────────────────────────────┘│
└──────────────────────────────────────────────────────┘
```

#### 2.3.2 Invoice Table

```
┌───────────────────────────────────────────────────────────────┐
│ 💰 Invoices                                                   │
├───────────────────────────────────────────────────────────────┤
│ 🔍 [Search...]  [Status: All ▼]  [Date: Last 30d ▼]  [📥 EX]│
├───────────────────────────────────────────────────────────────┤
│ # │ Customer    │ Date       │ Total    │ Status     │ Actions│
├───┼─────────────┼────────────┼──────────┼────────────┼────────┤
│ 1 │اللدائن الأهلية│ 12 Jul 26│EGP 42,500│ ✅ Submitted│ 👁 📄 │
│ 2 │بلاست مصر   │ 11 Jul 26│EGP 18,200│ 📝 Draft   │ ✏️ 🗑  │
│ 3 │الشركة العربية│ 10 Jul 26│EGP 95,000│ ✅ Submitted│ 👁 📄 │
│ 4 │صناعات الخليج│ 08 Jul 26│EGP 31,500│ ❌ Cancelled│ 👁    │
└───────────────────────────────────────────────────────────────┘
```

---

## 3. Rep PWA (Livewire — `/app`)

### 3.1 Layout

- **Top bar:** "جولة Jawla" logo (left), alarm bell with badge, user name, language toggle, dark mode toggle
- **Content area:** Card-based, full-width, scrollable
- **Bottom bar (mobile):** Home, Visits, Stock, Profile (icon + label)
- All buttons: `min-h-11` (44px touch targets)

### 3.2 Screen Prototypes

#### 3.2.1 Home Screen

```
┌─────────────────────┐
│ جولة Jawla  🔔  عرب │
├─────────────────────┤
│ السلام عليكم يا أحمد │
│                     │
│ 📍 ابدأ اليوم        │
│ [Start Work Button] │
│  big green button   │
│                     │
│ ┌───────┐ ┌───────┐ │
│ │ زيارات │ │ عروض  │ │
│ │ 5 today│ │ 2 pend│ │
│ └───────┘ └───────┘ │
│ ┌───────┐ ┌───────┐ │
│ │عملاء  │ │الصندوق│ │
│ │1 pend │ │EGP 2.5K│
│ └───────┘ └───────┘ │
│                     │
│ [🔍 ابحث عن منتج...] │
│ (stock search bar)  │
└─────────────────────┘
```

#### 3.2.2 Today's Visits

```
┌─────────────────────┐
│ ← زيارات اليوم      │
├─────────────────────┤
│ ☀️ 12 يوليو 2026    │
│                     │
│ ┌──────────────────┐│
│ │ 1  الشركة العربية ││
│ │    للبلاستيك     ││
│ │    6 أكتوبر      ││
│ │    🟢 ابدأ الزيارة││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ 2  بلاست مصر     ││
│ │    العاشر من رمضان││
│ │    🟢 ابدأ الزيارة││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ 3  اللدائن الأهلية││
│ │    العبور        ││
│ │    🟢 ابدأ الزيارة││
│ └──────────────────┘│
│ [+ إضافة عميل جديد]  │
└─────────────────────┘
```

#### 3.2.3 Visit Operations

```
┌─────────────────────┐
│ ← الشركة العربية    │
├─────────────────────┤
│ ✅ تم تأكيد الوصول   │
│ ✓ تم تسجيل التقرير  │
│                     │
│ العمليات:           │
│                     │
│ ┌──────────────────┐│
│ │ 💰 بيع           ││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ 💳 تحصيل         ││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ ↩ مرتجع          ││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ 📊 عرض سعر       ││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ 📄 فاتورة مبدئية  ││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ ⚠️ شكوى          ││
│ └──────────────────┘│
│ ┌──────────────────┐│
│ │ 📦 طلب عاجل نفذ  ││
│ └──────────────────┘│
│                     │
│ [✅ إنهاء الزيارة]   │
└─────────────────────┘
```

#### 3.2.4 Create Invoice

```
┌──────────────────────────────┐
│ ← فاتورة بيع                 │
├──────────────────────────────┤
│ العميل: الشركة العربية للبلاستيك│
│                              │
│ ┌──────────┬───┬─────┬─────┐ │
│ │ المنتج   │ الكمية│السعر│المجموع│
│ ├──────────┼───┼─────┼─────┤ │
│ │ PP 512   │ 2  │28,500│57,000│
│ │ (batch 2025-01)│     │     │ │
│ ├──────────┼───┼─────┼─────┤ │
│ │ PE 220   │ 1  │26,000│26,000│ │
│ │ (batch 2024-03)│     │     │ │
│ ├──────────┼───┼─────┼─────┤ │
│ │          │   │     │     │ │
│ │ [+ إضافة صنف]   │     │     │ │
│ └──────────┴───┴─────┴─────┘ │
│                              │
│ المجموع:             EGP 83,000│
│ ضريبة 14%:            EGP 11,620│
│ الإجمالي:            EGP 94,620│
│                              │
│ [💾 حفظ وإصدار الفاتورة]      │
│     big green button         │
└──────────────────────────────┘
```

#### 3.2.5 End Day Summary

```
┌─────────────────────┐
│ 📊 ملخص اليوم       │
├─────────────────────┤
│ ✅ تم إنهاء اليوم     │
│                     │
│ ┌─────────────────┐ │
│ │ الزيارات        │ │
│ │ 3 من 5 مكتملة   │ │
│ │ (60%)           │ │
│ └─────────────────┘ │
│ ┌─────────────────┐ │
│ │ المبيعات        │ │
│ │ EGP 142,500     │ │
│ └─────────────────┘ │
│ ┌─────────────────┐ │
│ │ التحصيلات       │ │
│ │ EGP 85,000     │ │
│ └─────────────────┘ │
│ ┌─────────────────┐ │
│ │ المرتجعات       │ │
│ │ EGP 12,000     │ │
│ └─────────────────┘ │
│ ┌─────────────────┐ │
│ │ المصروفات       │ │
│ │ EGP 450         │ │
│ └─────────────────┘ │
│ ┌─────────────────┐ │
│ │ رصيد الصندوق    │ │
│ │ EGP 2,450       │ │
│ └─────────────────┘ │
│                     │
│ [العودة إلى الرئيسية]│
└─────────────────────┘
```

### 3.3 GPS Geofence Component

```
State: Within 1 km
┌──────────────────────────────┐
│ 📍 الموقع: 6 أكتوبر          │
│ ✅ تم تأكيد الوصول —           │
│   أنت ضمن نطاق العميل         │
│   (المسافة: 350م)            │
└──────────────────────────────┘

State: Outside 1 km
┌──────────────────────────────┐
│ ⚠️ الموقع: مدينة نصر         │
│   أنت خارج نطاق العميل       │
│   (المسافة: 15 كم)           │
│ [تأكيد الوصول يدوياً]         │
│   (يتم تسجيل الموقع الحالي)  │
└──────────────────────────────┘
```

---

## 4. Invoice PDF Template

```
┌───────────────────────────────────────────────┐
│        شركة اللدائن العالمية                   │
│     Global Plastic Company (GPC)              │
│         📍 6 أكتوبر - الجيزة                  │
│     الرقم الضريبي: 618-549-994                │
│                                               │
│          فاتورة ضريبية / Tax Invoice           │
│           رقم: INV-GPC-2026-00042             │
│           التاريخ: 12 يوليو 2026               │
├───────────────────────────────────────────────┤
│ عميل: الشركة العربية للبلاستيك                │
│ Customer: El Arabia for Plastics             │
│ العنوان: 6 أكتوبر، الجيزة                    │
│ الرقم الضريبي: 123-456-789                   │
├───────────────────────────────────────────────┤
│ #│ الصنف              │الكمية│السعر│الإجمالي   │
│ 1│ PP 512              │  2   │28,500│ 57,000  │
│ 2│ PE 220              │  1   │26,000│ 26,000  │
├───────────────────────────────────────────────┤
│                      المجموع:      83,000 EGP │
│                      ضريبة 14%:   11,620 EGP │
│                      الإجمالي:    94,620 EGP │
├───────────────────────────────────────────────┤
│                              [QR code]        │
│                            ETA QR Code        │
│                                               │
│ بنك: QNB الأهلي                              │
│ حساب: 1234567890123456                       │
│ IBAN: EG12345678901234567890                 │
└───────────────────────────────────────────────┘
```

---

## 5. Responsive Breakpoints

| Breakpoint | Width      | Target              |
| ---------- | ---------- | ------------------- |
| Mobile     | < 640px    | Rep PWA primary     |
| Tablet     | 640-1024px | Admin panel usable  |
| Desktop    | > 1024px   | Admin panel primary |

- Rep PWA is always single-column, full-width
- Admin panel uses Filament's responsive grid (1 col mobile, 2 cols tablet, 3+ cols desktop)
- Tables horizontal scroll on mobile with sticky first column (name/ID)

---

## 6. Alarm Bell Component

```
┌──────────┐
│  🔔 ٣    │  ← badge with unread count (red dot if >0)
└──────────┘
  │
  ┌─────────────────────────────────┐
  │ 🔴 طلب عاجل: PP 512 نفذ من      │
  │    المخزون - منذ 5 دقائق        │
  │ 🟡 عميل جديد بانتظار الموافقة   │
  │    - منذ 15 دقيقة               │
  │ 🟢 طلب شراء: PE 220 من سابك     │
  │    - منذ ساعة                   │
  │                            [عرض الكل]│
  └─────────────────────────────────┘
```

---

## 7. Error & Empty States

- **Empty state:** Illustration + "لا توجد بيانات / No data" + CTA button
- **Loading state:** Skeleton cards (gray-200 pulsing) for list items
- **Error state:** Red banner with bilingual message + "إعادة المحاولة / Retry" button
- **Offline state (rep PWA):** Yellow banner "أنت غير متصل / You are offline — بعض الخدمات غير متاحة"
- **Form validation:** Red border on invalid field + Arabic error below + English in parentheses
- **404:** "الصفحة غير موجودة / Page not found" + "العودة للرئيسية / Go Home" button
