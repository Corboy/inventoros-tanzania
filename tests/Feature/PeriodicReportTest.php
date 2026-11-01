<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auth\Organization;
use App\Models\Inventory\Product;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Role;
use App\Models\System\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PeriodicReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::set('installed', true, 'boolean');
        $this->seed(RoleSeeder::class);

        $this->organization = Organization::create([
            'name' => 'Kariakoo Wholesale Enterprises',
            'email' => 'kariakoo@enterprise.co.tz',
            'currency' => 'TZS',
            'timezone' => 'Africa/Dar_es_Salaam',
        ]);

        $adminRole = Role::where('slug', 'system-administrator')->first();

        $this->admin = User::create([
            'name' => 'Bwana Biashara',
            'email' => 'boss@kariakoo.co.tz',
            'password' => bcrypt('password123'),
            'organization_id' => $this->organization->id,
            'role_id' => $adminRole?->id,
        ]);
        $this->admin->forceFill(['role' => 'admin'])->save();
    }

    public function test_can_generate_daily_sales_report_for_today(): void
    {
        $product = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Redmi Note 13',
            'sku' => 'RDM-13',
            'price' => 450000,
            'purchase_price' => 380000,
            'stock' => 20,
            'min_stock' => 2,
            'currency' => 'TZS',
        ]);

        // Order placed today
        $order = Order::create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->admin->id,
            'order_number' => 'ORD-TD-01',
            'customer_name' => 'Salum Ally',
            'status' => 'delivered',
            'subtotal' => 450000,
            'tax' => 0,
            'shipping' => 0,
            'total' => 450000,
            'currency' => 'TZS',
            'order_date' => now(),
            'metadata' => [
                'payment_method' => 'mpesa',
                'cash_tendered' => 450000,
                'change_due' => 0,
            ],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 450000,
            'subtotal' => 450000,
            'tax' => 0,
            'total' => 450000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.sales-analysis', ['period' => 'today']));
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/SalesAnalysis')
            ->where('filters.period', 'today')
            ->where('filters.granularity', 'hour')
            ->where('summary.total_orders', 1)
            ->where('summary.total_revenue', 450000)
            ->where('summary.total_cost', 380000)
            ->where('summary.gross_profit', 70000)
            ->has('paymentMethods', 1)
            ->where('paymentMethods.0.method', 'mpesa')
            ->has('salesByUser', 1)
            ->has('dailySales')
        );
    }

    public function test_can_generate_weekly_monthly_and_yearly_reports(): void
    {
        $product = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Flash Drive 32GB',
            'sku' => 'FD-32',
            'price' => 20000,
            'purchase_price' => 12000,
            'stock' => 50,
            'min_stock' => 5,
            'currency' => 'TZS',
        ]);

        $order = Order::create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->admin->id,
            'order_number' => 'ORD-YR-01',
            'customer_name' => 'Hamis Juma',
            'status' => 'delivered',
            'subtotal' => 40000,
            'tax' => 0,
            'shipping' => 0,
            'total' => 40000,
            'currency' => 'TZS',
            'order_date' => now(),
            'metadata' => ['payment_method' => 'cash'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 20000,
            'subtotal' => 40000,
            'tax' => 0,
            'total' => 40000,
        ]);

        // 1. Weekly
        $resWeek = $this->actingAs($this->admin)->get(route('reports.sales-analysis', ['period' => 'this_week']));
        $resWeek->assertStatus(200);
        $resWeek->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', 'this_week')
            ->where('summary.total_orders', 1)
            ->where('summary.total_revenue', 40000)
            ->where('summary.total_cost', 24000)
            ->where('summary.gross_profit', 16000)
        );

        // 2. Monthly
        $resMonth = $this->actingAs($this->admin)->get(route('reports.sales-analysis', ['period' => 'this_month']));
        $resMonth->assertStatus(200);
        $resMonth->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', 'this_month')
            ->where('summary.total_orders', 1)
        );

        // 3. Yearly
        $resYear = $this->actingAs($this->admin)->get(route('reports.sales-analysis', ['period' => 'this_year']));
        $resYear->assertStatus(200);
        $resYear->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', 'this_year')
            ->where('filters.granularity', 'month')
            ->where('summary.total_orders', 1)
        );
    }

    public function test_can_download_csv_export_of_report(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.sales-analysis', [
            'period' => 'this_month',
            'export' => 'csv',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('filename=ripoti-ya-mauzo-this_month', (string) $response->headers->get('Content-Disposition'));
    }
}
