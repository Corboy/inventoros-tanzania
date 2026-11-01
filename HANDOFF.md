# INVENTOROS TANZANIA — TECHNICAL HANDOFF DOSSIER

> **Purpose of this Document:**  
> This file is the primary briefing document for any incoming AI Agent (or human software engineer) taking over this codebase. It documents the current working state, architectural decisions, recent enhancements, test suites, known nuances, and the development roadmap.
>
> **Repository:** `https://github.com/Corboy/inventoros-tanzania.git`  
> **Active Branch:** `main`  
> **Status:** ✅ **Production Ready (1,405 Automated Tests Passing • 0 Failures)**

---

## 1. Project Overview & Tech Stack

**Inventoros Tanzania** is a customized, modern, high-speed Point of Sale (POS) and Multi-Branch Inventory Management System tailored specifically for the Tanzanian business ecosystem (retailers, wholesalers, hardware shops, pharmacies, supermarkets, and electronics stores).

### Core Stack:
- **Backend Framework:** Laravel 11.x (PHP 8.2+)
- **Frontend Framework:** Vue 3 (Composition API `<script setup>`)
- **SPA Bridge:** Inertia.js 1.x
- **Styling:** Tailwind CSS 3.x with custom design tokens (`resources/css/design-tokens.css`)
- **Icons:** Lucide Icons (`lucide-vue-next`)
- **Database:** SQLite (default local development and single-store standalone: `database/database.sqlite`), fully compatible with MySQL 8.x and PostgreSQL 15.x
- **Build Tool:** Vite 5.x (`npm run build`, `npm run dev`)
- **Routing:** Ziggy (`@tightenco/ziggy`) for Vue client-side route helpers

---

## 2. Recent Major Enhancements & Work Completed

The following modules and fixes have been thoroughly implemented, tested, and audited:

### A. Tanzanian Commercial Localization (`resources/js/i18n/locales/sw.json`)
- Replaced stiff dictionary Swahili with natural Tanzanian business terminology:
  - *POS / Create Order* ➔ **Kaunta ya Mauzo (POS)**
  - *Pending Orders* ➔ **Oda Zinazosubiri**
  - *Stock Adjustments* ➔ **Marekebisho ya Stoo**
  - *Cash Tendered / Change Due* ➔ **Pesa Iliyopokelewa / Chenji ya Mteja**
  - *Walk-in Customer* ➔ **Mteja wa Dukani / Kaunta**
  - *Out of Stock / Low Stock / Stagnant* ➔ **Zilizoisha / Zilizobaki Kidogo / Zilizokaa Muda Mrefu**
  - *Wholesale Price / Retail Price* ➔ **Bei ya Jumla / Bei ya Rejareja**

### B. POS Counter Enhancements (`resources/js/Pages/Orders/Create.vue`)
- **Wholesale Price Toggle:** Added one-click toggle per cart line item between Retail Price (`price`) and Wholesale Price (`wholesale_price`).
- **Quantity Stepper Re-engineering:**
  - Expanded input box width from cramped `w-11` (44px) to `w-16` (64px) with `px-1 text-sm font-bold`.
  - Added global and component-level CSS `[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none` to eliminate browser native spin arrows that were covering digits (especially `0` on numbers like `50`, `100`).
  - Added safe typing & blur normalization (`normalizeItemQuantity`) so typing `0` or clearing the input does not unexpectedly delete the product line.
  - Safe subtotal calculations protecting against `NaN`.
- **Cash Tendered & Change Due Calculations:**
  - Real-time calculation of customer change.
  - Red visual alert with exact shortage amount if cash tendered is less than the total.
- **Shortcuts:** <kbd>Ctrl + Enter</kbd> (or <kbd>Cmd + Enter</kbd>) to instantly complete the sale.

### C. Thermal Receipt & Invoice Printing (`resources/js/Components/PosReceiptModal.vue`)
- Thermal receipt generator formatted specifically for 58mm and 80mm ESC/POS thermal receipt printers (Epson, Xprinter, Sunmi, etc.).
- Displays organization name, address, phone, TIN, receipt number, cashier name, line items, cash tendered, change due, and Swahili thank-you footer.
- Clean `@media print` layout that hides all browser UI chrome.

### D. Comprehensive Periodic Reporting Engine (`ReportController.php` & `SalesAnalysis.vue`)
- **One-Tap Period Presets:**
  - `Leo` (Today - hourly breakdown)
  - `Jana` (Yesterday)
  - `Wiki Hii` (This Week)
  - `Siku 7` (Last 7 Days)
  - `Mwezi Huu` (This Month - daily breakdown)
  - `Mwezi Uliopita` (Last Month)
  - `Mwaka Huu` (This Year - monthly breakdown)
  - `Kipindi Maalum` (Custom Date Range picker)
- **Financial Metrics:**
  - **Gross Revenue (Jumla ya Mapato)**
  - **Cost of Goods Sold (COGS / Gharama za Bidhaa Zilizouzwa):** Calculated via `order_items` joined with `products.purchase_price`.
  - **Gross Profit (Faida Halisi):** `Revenue - COGS`.
  - **Profit Margin % (Uwiano wa Faida):** `(Profit / Revenue) * 100`.
- **Insight Breakdowns:**
  - Granular time trend bar chart.
  - Payment Methods breakdown (Cash, M-Pesa, TigoPesa, Airtel Money, Bank).
  - Cashier performance table (orders and revenue per staff member).
  - Top-selling products table with profit and margin per SKU.
- **Exports & Printing:**
  - Clean `@media print` layout for printer/PDF.
  - Streaming CSV/Excel export (`?export=csv`) formatted with UTF-8 BOM for Microsoft Excel.

### E. Inventory & 3-Tier Alert System
- **3 Critical Stock Statuses:**
  1. ⚠️ **Zilizobaki Kidogo (Low Stock):** Items below `min_stock` threshold.
  2. ❌ **Zilizoisha Kabisa (Out of Stock):** Items with 0 quantity.
  3. ⏳ **Zilizokaa Muda Mrefu (Stagnant):** Slow-moving stock without recent sales.
- **Stock Adjustments (`StockAdjustments/`):** Track damaged goods, expirations, loss/theft, and physical recount corrections.
- **Wholesale Price & Unit Migration:** Added `wholesale_price` (decimal 12,2 nullable) and `unit` (string nullable, e.g. Mfuko, Pcs, Ndoo, Lita, Kg) to the `products` table via migration `database/migrations/2026_09_03_232812_add_wholesale_price_and_unit_to_products_table.php`.

### F. Desktop Sidebar Toggle (`resources/js/Layouts/AppLayout.vue`)
- Collapsible sidebar for POS counters with <kbd>Ctrl + B</kbd> (or <kbd>Cmd + B</kbd>) and header button toggle.
- State persists across page refreshes via `localStorage`.

### G. Critical Bug Fixes:
1. **`Show.vue` (Product Details):** Added missing `import { useCurrency }` composable import.
2. **`QuickAddModal.vue`:** Added missing `import { useI18n }` composable import.
3. **`Reports/Index.vue`:** Corrected Ziggy route reference from `reports.lowStock` to `reports.low-stock`.
4. **`WarehouseSwitcher.vue`:** Corrected route call from `route('set-warehouse')` to `route('warehouses.set-active')`.

---

## 3. How to Run, Test, and Build

### Start the Application:
```bash
# 1. Navigate to the repo
cd "/Users/imac/nying timba/ENVENTORY/inventoros-tanzania"

# 2. Serve with PHP (opcache disabled for CLI live development)
php -d opcache.enable_cli=0 artisan serve --host=127.0.0.1 --port=8000

# 3. Access in browser
http://127.0.0.1:8000
```

### Compile Frontend Assets:
```bash
# Production compile (minified, tree-shaken chunks in public/build)
npm run build

# Development hot-reload
npm run dev
```

### Run Tests:
```bash
# Run all automated tests (1,405 tests)
php artisan test

# Run periodic reports test
php artisan test tests/Feature/PeriodicReportTest.php tests/Feature/ReportControllerTest.php

# Run full system E2E smoke test
php artisan test tests/Feature/FullSystemE2ESmokeTest.php

# Run live database audit (verifies all 36 screens against database.sqlite)
DB_DATABASE=database/database.sqlite php artisan test tests/Feature/LiveDatabaseAuditTest.php
```

---

## 4. Default Seeded Accounts & Credentials

The database is seeded with authentic Tanzanian business data (`database/seeders/TanzaniaBusinessSeeder.php`):

| Role | Name | Email | Password | Permissions Scope |
| :--- | :--- | :--- | :--- | :--- |
| **Mkurugenzi (Admin)** | Juma Rashidi | `admin@biashara.co.tz` | `password123` | Full access (finance, reports, users, settings, stock) |
| **Meneja wa Stoo (Manager)** | Neema Mwangi | `meneja@biashara.co.tz` | `password123` | Inventory, purchase orders, receiving, stock adjustments |
| **Muuzaji wa Kaunta (Cashier)** | Baraka John | `muuzaji@biashara.co.tz` | `password123` | POS counter, sales creation, receipts (no financial reports) |

---

## 5. Critical Gotchas & Architectural Rules (READ BEFORE CODING)

1. **`OrderStatus` is a PHP 8.1 BackedEnum:**
   In `App\Models\Order\Order`, `status` is cast to `App\Enums\OrderStatus::class`.
   - **DO NOT** do `(string) $order->status` directly without checking if it is an instance of `\BackedEnum`.
   - **Correct Pattern:**
     ```php
     $statusVal = $order->status instanceof \BackedEnum ? $order->status->value : (string) ($order->status ?? '');
     ```
2. **SQL Column Ambiguity in Joins:**
   Both `orders` and `users` tables share column names such as `organization_id`, `created_at`, `updated_at`, and `deleted_at`.
   - When writing `join` queries in `ReportController.php` or queries, **always explicitly qualify column names** (e.g. `orders.organization_id`, `orders.order_date`, `orders.deleted_at`).
3. **Multi-Tenancy Isolation (`organization_id`):**
   - Every single model (`Product`, `Order`, `Customer`, `Warehouse`, `Category`) belongs to an organization.
   - All tenant queries MUST use `forOrganization($orgId)` or verify `organization_id` to maintain multi-tenant data isolation.
4. **Order Metadata Storage:**
   - Extra Tanzanian fields like `cash_tendered`, `change_due`, `payment_method`, `customer_tin`, and `customer_phone` are stored inside the `orders.metadata` JSON column.
   - Do not alter the base database schema unless strictly necessary; use `metadata` accessors on `App\Models\Order\Order`.
5. **Ziggy Route Verification:**
   - Any time you add a link in Vue templates using `route('...')`, ensure the route name exactly matches `php artisan route:list`. A typo in Ziggy will throw an uncaught JS error that can blank out the page.

---

## 6. Development Roadmap for the Next Agent

If the client or user requests further additions, here are the ready extension points:

1. **TRA VFD Integration (Tanzania Revenue Authority EFD/VFD):**
   - *Requirements:* TIN, CertKey, private key signing, and QR code generation for fiscal receipts.
   - *Extension Point:* Hook into `App\Services\OrderService::createOrder` or an event listener on `OrderCreated` to submit invoice data to TRA API and store `vfd_receipt_number`, `vfd_qr_url` in `order.metadata`.
2. **Mobile SMS Gateway (Customer Purchase SMS):**
   - *Providers:* Beem Africa (`api.beem.africa`) or NextSMS.
   - *Extension Point:* Dispatch a queued job `SendOrderReceiptSmsJob` on order completion.
3. **Automated Lipa Namba USSD Push:**
   - Integration with mobile money aggregator (e.g., DPO Pay, Flutterwave, or direct M-Pesa Daraja C2B/B2C API) for automated callback reconciliation.
4. **Progressive Web App (PWA) / Offline Cache:**
   - Register a service worker with CacheStorage to enable full offline sales synchronization if internet drops on tablet devices.

---

*Handoff compiled and audited by the Lead Agent on November 1, 2026.*
