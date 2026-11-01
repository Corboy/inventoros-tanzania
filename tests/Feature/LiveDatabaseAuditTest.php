<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

final class LiveDatabaseAuditTest extends TestCase
{
    // Note: No RefreshDatabase trait here! We test against the real seeded database.

    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->admin = User::where('email', 'admin@biashara.co.tz')->first();
        } catch (\Throwable $e) {
            $this->admin = null;
        }
    }

    public function test_all_live_screens_and_buttons_accessible(): void
    {
        if (!$this->admin) {
            $this->markTestSkipped('admin@biashara.co.tz not found');
        }

        $screens = [
            'Dashboard' => route('dashboard'),
            'Products List' => route('products.index'),
            'Product 1 Show' => '/products/1',
            'Create Product' => route('products.create'),
            'POS Screen' => route('orders.create'),
            'Orders List' => route('orders.index'),
            'Customers List' => route('customers.index'),
            'Create Customer' => route('customers.create'),
            'Suppliers List' => route('suppliers.index'),
            'Create Supplier' => route('suppliers.create'),
            'Purchase Orders' => route('purchase-orders.index'),
            'Create Purchase Order' => route('purchase-orders.create'),
            'Categories' => route('categories.index'),
            'Locations' => route('locations.index'),
            'Warehouses' => route('warehouses.index'),
            'Reports Overview' => route('reports.index'),
            'Sales Analysis (Default)' => route('reports.sales-analysis'),
            'Sales Analysis (Today)' => route('reports.sales-analysis', ['period' => 'today']),
            'Sales Analysis (Yesterday)' => route('reports.sales-analysis', ['period' => 'yesterday']),
            'Sales Analysis (This Week)' => route('reports.sales-analysis', ['period' => 'this_week']),
            'Sales Analysis (Last 7 Days)' => route('reports.sales-analysis', ['period' => 'last_7_days']),
            'Sales Analysis (This Month)' => route('reports.sales-analysis', ['period' => 'this_month']),
            'Sales Analysis (Last Month)' => route('reports.sales-analysis', ['period' => 'last_month']),
            'Sales Analysis (This Year)' => route('reports.sales-analysis', ['period' => 'this_year']),
            'Sales Analysis (Last Year)' => route('reports.sales-analysis', ['period' => 'last_year']),
            'Sales Analysis (CSV Export)' => route('reports.sales-analysis', ['period' => 'this_month', 'export' => 'csv']),
            'Inventory Valuation' => route('reports.inventory-valuation'),
            'Low Stock Report' => route('reports.low-stock'),
            'Stock Adjustments' => route('stock-adjustments.index'),
            'Create Adjustment' => route('stock-adjustments.create'),
            'Users Management' => route('users.index'),
            'Create User' => route('users.create'),
            'Roles Management' => route('roles.index'),
            'Create Role' => route('roles.create'),
            'Organization Settings' => route('settings.organization.index'),
            'Account Settings' => route('settings.account.index'),
        ];

        foreach ($screens as $name => $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertTrue(
                $response->isOk(),
                "Failed asserting that screen '{$name}' [{$url}] returns 200 OK. Got status: " . $response->getStatusCode()
            );
        }
    }
}
