<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auth\Organization;
use App\Models\Customer;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductLocation;
use App\Models\Inventory\Supplier;
use App\Models\Order\Order;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Role;
use App\Models\System\SystemSetting;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class FullSystemE2ESmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Organization $organization;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::set('installed', true, 'boolean');

        $this->seed(RoleSeeder::class);

        $this->organization = Organization::create([
            'name' => 'Biashara Bora Tanzania',
            'email' => 'info@biasharabora.co.tz',
            'currency' => 'TZS',
            'timezone' => 'Africa/Dar_es_Salaam',
        ]);

        $adminRole = Role::where('slug', 'system-administrator')->first();

        $this->admin = User::create([
            'name' => 'Mkurugenzi Mkuu',
            'email' => 'admin@biasharabora.co.tz',
            'password' => bcrypt('password123'),
            'organization_id' => $this->organization->id,
            'role_id' => $adminRole?->id,
        ]);
        $this->admin->forceFill(['role' => 'admin'])->save();

        $this->warehouse = Warehouse::create([
            'organization_id' => $this->organization->id,
            'name' => 'Kariakoo Kuu',
            'code' => 'KARI-01',
            'is_default' => true,
        ]);
    }

    public function test_dashboard_button_and_metrics_render_cleanly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->has('stockAlerts')
            ->has('topProducts')
            ->has('recentOrders')
        );
    }

    public function test_products_full_lifecycle_buttons(): void
    {
        // 1. Index page
        $this->actingAs($this->admin)->get(route('products.index'))->assertStatus(200);

        // 2. Create page
        $this->actingAs($this->admin)->get(route('products.create'))->assertStatus(200);

        // 3. Category creation
        $catResponse = $this->actingAs($this->admin)->post(route('categories.store'), [
            'name' => 'Vifaa vya Kielektroniki',
            'description' => 'Simu, laptop na vifaa vyote vya umeme',
        ]);
        $catResponse->assertSessionHasNoErrors();
        $category = ProductCategory::where('name', 'Vifaa vya Kielektroniki')->firstOrFail();

        // 4. Store product (with retail and wholesale prices)
        $storeResponse = $this->actingAs($this->admin)->post(route('products.store'), [
            'name' => 'Samsung Galaxy A15 128GB',
            'category_id' => $category->id,
            'price' => 380000,
            'wholesale_price' => 350000,
            'purchase_price' => 320000,
            'currency' => 'TZS',
            'stock' => 25,
            'min_stock' => 5,
            'unit' => 'pcs',
            'type' => 'standard',
        ]);
        $storeResponse->assertSessionHasNoErrors();

        $product = Product::where('name', 'Samsung Galaxy A15 128GB')->firstOrFail();
        $this->assertNotEmpty($product->sku, 'SKU should be auto-generated');
        $this->assertEquals(25, $product->stock);
        $this->assertEquals(350000, (float) $product->wholesale_price);

        // 5. Show product (/products/{id})
        $showResponse = $this->actingAs($this->admin)->get(route('products.show', $product));
        $showResponse->assertStatus(200);
        $showResponse->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product')
        );

        // 6. Edit product form
        $editResponse = $this->actingAs($this->admin)->get(route('products.edit', $product));
        $editResponse->assertStatus(200);

        // 7. Update product
        $updateResponse = $this->actingAs($this->admin)->put(route('products.update', $product), [
            'name' => 'Samsung Galaxy A15 128GB 5G',
            'price' => 395000,
            'wholesale_price' => 360000,
            'stock' => 25,
            'min_stock' => 5,
            'currency' => 'TZS',
            'sku' => $product->sku,
            'category_id' => $category->id,
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertEquals('Samsung Galaxy A15 128GB 5G', $product->fresh()->name);

        // 8. Barcode generation
        $barcodeResponse = $this->actingAs($this->admin)->post(route('products.barcode.generate-from-sku', $product));
        $barcodeResponse->assertStatus(200);
        $this->assertNotEmpty($product->fresh()->barcode);
    }

    public function test_customer_lifecycle_buttons(): void
    {
        // 1. Index
        $this->actingAs($this->admin)->get(route('customers.index'))->assertStatus(200);

        // 2. Create form
        $this->actingAs($this->admin)->get(route('customers.create'))->assertStatus(200);

        // 3. Store customer
        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'name' => 'Mussa Juma Baraka',
            'email' => 'mussa.juma@gmail.com',
            'phone' => '0754112233',
            'address' => 'Msimbazi St, Kariakoo, Dar es Salaam',
            'notes' => 'Mteja wa jumla - duka la Morogoro',
        ]);
        $response->assertSessionHasNoErrors();
        $customer = Customer::where('name', 'Mussa Juma Baraka')->firstOrFail();

        // 4. Show customer
        $this->actingAs($this->admin)->get(route('customers.show', $customer))->assertStatus(200);

        // 5. Edit form
        $this->actingAs($this->admin)->get(route('customers.edit', $customer))->assertStatus(200);

        // 6. Update customer
        $updateResponse = $this->actingAs($this->admin)->put(route('customers.update', $customer), [
            'name' => 'Mussa Juma Baraka (Wholesale)',
            'phone' => '0754112233',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertEquals('Mussa Juma Baraka (Wholesale)', $customer->fresh()->name);
    }

    public function test_supplier_and_purchase_order_receiving_lifecycle(): void
    {
        // 1. Suppliers index & create
        $this->actingAs($this->admin)->get(route('suppliers.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('suppliers.create'))->assertStatus(200);

        // 2. Create supplier
        $suppResponse = $this->actingAs($this->admin)->post(route('suppliers.store'), [
            'name' => 'Kariakoo Mega Importers Ltd',
            'contact_person' => 'Rashid Salim',
            'email' => 'rashid@megaimporters.co.tz',
            'phone' => '0788112233',
            'address' => 'Uhuru St, Dar es Salaam',
        ]);
        $suppResponse->assertSessionHasNoErrors();
        $supplier = Supplier::where('name', 'Kariakoo Mega Importers Ltd')->firstOrFail();

        // 3. Create a product to order
        $product = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Chaja ya Type-C Fast 30W',
            'sku' => 'CHG-30W',
            'price' => 25000,
            'purchase_price' => 12000,
            'stock' => 10,
            'min_stock' => 5,
            'currency' => 'TZS',
        ]);

        // 4. Purchase orders index & create form
        $this->actingAs($this->admin)->get(route('purchase-orders.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('purchase-orders.create'))->assertStatus(200);

        // 5. Create Purchase Order
        $poResponse = $this->actingAs($this->admin)->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(3)->toDateString(),
            'currency' => 'TZS',
            'notes' => 'Shehena mpya ya chaja za simu',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 50,
                    'unit_cost' => 12000,
                ],
            ],
        ]);
        $poResponse->assertSessionHasNoErrors();
        $purchaseOrder = PurchaseOrder::where('supplier_id', $supplier->id)->firstOrFail();

        // 6. View PO
        $this->actingAs($this->admin)->get(route('purchase-orders.show', $purchaseOrder))->assertStatus(200);

        // 7. Send PO to supplier, then receive goods into stock
        $this->actingAs($this->admin)->post(route('purchase-orders.send', $purchaseOrder));
        $receiveResponse = $this->actingAs($this->admin)->post(route('purchase-orders.process-receiving', $purchaseOrder), [
            'items' => [
                [
                    'id' => $purchaseOrder->items->first()->id,
                    'quantity_to_receive' => 50,
                ],
            ],
        ]);
        $receiveResponse->assertSessionHasNoErrors();

        // Stock should now be 10 + 50 = 60
        $this->assertEquals(60, $product->fresh()->stock);
    }

    public function test_pos_sale_and_invoice_receipt_buttons(): void
    {
        // 1. Create product
        $product = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Headphones Sony WH-1000XM4',
            'sku' => 'SNY-WH1000',
            'price' => 650000,
            'wholesale_price' => 600000,
            'stock' => 15,
            'min_stock' => 2,
            'currency' => 'TZS',
        ]);

        // 2. Orders index & POS create screen
        $this->actingAs($this->admin)->get(route('orders.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('orders.create'))->assertStatus(200);

        // 3. Make POS Cash Sale (Walk-in Customer with Cash Tendered and Change Due)
        $orderResponse = $this->actingAs($this->admin)->post(route('orders.store'), [
            'customer_name' => 'Mteja wa Dukani',
            'payment_method' => 'cash',
            'cash_tendered' => 700000,
            'change_due' => 50000,
            'status' => 'delivered',
            'order_date' => now()->toDateString(),
            'currency' => 'TZS',
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 650000,
                ],
            ],
        ]);
        $orderResponse->assertSessionHasNoErrors();

        $order = Order::where('customer_name', 'Mteja wa Dukani')->firstOrFail();
        $this->assertEquals(650000, (float) $order->total);
        $this->assertEquals(700000, (float) $order->cash_tendered);
        $this->assertEquals(50000, (float) $order->change_due);

        // Stock deducted from 15 to 14
        $this->assertEquals(14, $product->fresh()->stock);

        // 4. View order details
        $this->actingAs($this->admin)->get(route('orders.show', $order))->assertStatus(200);

        // 5. Thermal receipt / invoice view
        $invoiceResponse = $this->actingAs($this->admin)->get(route('orders.invoice.preview', $order));
        $invoiceResponse->assertStatus(200);
    }

    public function test_reports_and_analytics_endpoints(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.sales-analysis'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.inventory-valuation'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.low-stock'))->assertStatus(200);
    }

    public function test_settings_roles_and_users_screens(): void
    {
        $this->actingAs($this->admin)->get(route('settings.index'))->assertRedirect(route('settings.organization.index'));
        $this->actingAs($this->admin)->get(route('settings.account.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('settings.organization.index'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('users.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('users.create'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('roles.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('roles.create'))->assertStatus(200);
    }

    public function test_stock_adjustments_and_locations_buttons(): void
    {
        $product = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Flash Drive 64GB SanDisk',
            'sku' => 'FL-SNDK-64',
            'price' => 35000,
            'stock' => 10,
            'min_stock' => 2,
            'currency' => 'TZS',
        ]);

        // Stock adjustments list and create form
        $this->actingAs($this->admin)->get(route('stock-adjustments.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('stock-adjustments.create'))->assertStatus(200);

        // Stock adjustment store
        $adjResponse = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [
            'product_id' => $product->id,
            'adjustment_quantity' => -2,
            'type' => 'damage',
            'reason' => 'Vifaa vilivyoharibika',
            'notes' => 'Zilianguka na kuvunjika wakati wa kushusha mzigo',
        ]);
        $adjResponse->assertSessionHasNoErrors();
        $this->assertEquals(8, $product->fresh()->stock);

        // Locations list and create
        $this->actingAs($this->admin)->get(route('locations.index'))->assertStatus(200);
        $locResponse = $this->actingAs($this->admin)->post(route('locations.store'), [
            'name' => 'Shelf A-12',
            'code' => 'SH-A12',
            'type' => 'shelf',
            'description' => 'Rafu ya vifaa vya kuhifadhia data',
        ]);
        $locResponse->assertSessionHasNoErrors();
    }
}
