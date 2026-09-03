<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Auth\Organization;
use App\Models\Customer;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductLocation;
use App\Models\Inventory\Supplier;
use App\Models\System\SystemSetting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TanzaniaBusinessSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Mark system as installed
        SystemSetting::set('installed', true, 'boolean', 'Installation completed');
        SystemSetting::set('installed_at', now()->toDateTimeString(), 'string', 'Installation date');
        SystemSetting::set('app_version', config('app.version', '1.0.0'), 'string', 'Application version');

        // 1. Roles
        $this->call(RoleSeeder::class);

        // 2. Organization
        $org = Organization::updateOrCreate(
            ['name' => 'Kariakoo Hardware & Smart Store Ltd'],
            [
                'address' => 'Msimbazi & Swahili Street, Kariakoo',
                'city' => 'Dar es Salaam',
                'state' => 'Dar es Salaam',
                'zip' => '11101',
                'country' => 'Tanzania',
                'phone' => '+255 712 345 678',
                'email' => 'info@kariakoohardware.co.tz',
                'currency' => 'TZS',
                'timezone' => 'Africa/Dar_es_Salaam',
            ]
        );
        $orgId = $org->id;

        // 3. Users with Known Credentials
        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@biashara.co.tz'],
            [
                'name' => 'Juma Rashidi (Mkurugenzi)',
                'password' => Hash::make('password123'),
                'organization_id' => $orgId,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('system-administrator');

        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'meneja@biashara.co.tz'],
            [
                'name' => 'Neema Mwangi (Meneja wa Stoo)',
                'password' => Hash::make('password123'),
                'organization_id' => $orgId,
                'role' => 'manager',
                'email_verified_at' => now(),
            ]
        );
        $manager->assignRole('system-manager');

        // Cashier / Staff
        $cashier = User::updateOrCreate(
            ['email' => 'muuzaji@biashara.co.tz'],
            [
                'name' => 'Baraka Salum (Muuzaji wa Kaunta)',
                'password' => Hash::make('password123'),
                'organization_id' => $orgId,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );
        $cashier->assignRole('system-member');

        // 4. Warehouse & Locations
        $warehouse = Warehouse::updateOrCreate(
            ['code' => 'GH-DAR-01', 'organization_id' => $orgId],
            [
                'name' => 'Ghala Kuu la Kariakoo (Main Warehouse)',
                'description' => 'Ghala Kuu la Kuhifadhia Mzigo Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'phone' => '+255 712 345 678',
                'email' => 'ghala@kariakoohardware.co.tz',
                'currency' => 'TZS',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $locMain = ProductLocation::updateOrCreate(
            ['name' => 'Rafu A - Vifaa vya Ujenzi', 'organization_id' => $orgId],
            ['description' => 'Sehemu ya saruji, nondo na mabati', 'is_active' => true]
        );

        $locStorefront = ProductLocation::updateOrCreate(
            ['name' => 'Kaunta ya Mauzo (Storefront)', 'organization_id' => $orgId],
            ['description' => 'Bidhaa za kuonyesha dukani kwa wateja', 'is_active' => true]
        );

        // 5. Categories
        $catHardware = ProductCategory::updateOrCreate(
            ['name' => 'Vifaa vya Ujenzi', 'organization_id' => $orgId],
            ['description' => 'Saruji, nondo, mabati na misumari']
        );

        $catElectrical = ProductCategory::updateOrCreate(
            ['name' => 'Vifaa vya Umeme', 'organization_id' => $orgId],
            ['description' => 'Taa, waya, soketi na switch za umeme']
        );

        $catPlumbing = ProductCategory::updateOrCreate(
            ['name' => 'Vifaa vya Maji na Mabomba', 'organization_id' => $orgId],
            ['description' => 'Mabomba ya PPR/PVC, koki na vifaa vya bafu']
        );

        // 6. Suppliers
        Supplier::updateOrCreate(
            ['name' => 'Twiga Cement Tanzania Ltd', 'organization_id' => $orgId],
            [
                'contact_name' => 'Ofisi ya Mauzo',
                'email' => 'sales@twigacement.co.tz',
                'phone' => '+255 22 216 7800',
                'currency' => 'TZS',
                'is_active' => true,
            ]
        );

        Supplier::updateOrCreate(
            ['name' => 'Simba Pipe & Steel Industries Ltd', 'organization_id' => $orgId],
            [
                'contact_name' => 'Idara ya Usambazaji',
                'email' => 'orders@simbasteel.co.tz',
                'phone' => '+255 754 112 233',
                'currency' => 'TZS',
                'is_active' => true,
            ]
        );

        // 7. Customers
        Customer::updateOrCreate(
            ['name' => 'Mhandisi Makao Construction Ltd', 'organization_id' => $orgId],
            [
                'company_name' => 'Makao Construction Ltd',
                'email' => 'makao@construct.co.tz',
                'phone' => '+255 784 990 011',
                'tax_id' => '102-445-889',
                'credit_limit' => 5000000,
                'currency' => 'TZS',
                'is_active' => true,
            ]
        );

        // 8. Products with Tanzanian Pricing
        $products = [
            [
                'name' => 'Saruji ya Twiga Extra 50kg (Twiga Cement)',
                'sku' => 'TWG-CEM-50',
                'category_id' => $catHardware->id,
                'location_id' => $locMain->id,
                'purchase_price' => 18500,
                'price' => 22000,
                'stock' => 250,
                'min_stock' => 20,
                'description' => 'Mufuko wa 50kg wa saruji ya kujenga na plasta',
            ],
            [
                'name' => 'Nondo za Milimita 12mm (High Tensile Rebar)',
                'sku' => 'NON-12MM-01',
                'category_id' => $catHardware->id,
                'location_id' => $locMain->id,
                'purchase_price' => 26000,
                'price' => 31000,
                'stock' => 140,
                'min_stock' => 15,
                'description' => 'Nondo imara za ujenzi urefu wa futi 40 (12m)',
            ],
            [
                'name' => 'Mabati ya Rangi Mgongo wa Mbuzi Gauge 28 (3m)',
                'sku' => 'MAB-G28-BLU',
                'category_id' => $catHardware->id,
                'location_id' => $locMain->id,
                'purchase_price' => 23000,
                'price' => 28500,
                'stock' => 85,
                'min_stock' => 10,
                'description' => 'Mabati ya rangi ya bluu urefu wa mita 3',
            ],
            [
                'name' => 'Misumari ya Bati yenye Kofia (Roofing Nails 2.5")',
                'sku' => 'MSM-BAT-25',
                'category_id' => $catHardware->id,
                'location_id' => $locMain->id,
                'purchase_price' => 4500,
                'price' => 6500,
                'stock' => 50,
                'min_stock' => 5,
                'description' => 'Misumari ya bati yenye gasket kuzuia kuvuja',
            ],
            [
                'name' => 'Taa ya LED Philips 12W Daylight (LED Bulb)',
                'sku' => 'TAA-LED-12W',
                'category_id' => $catElectrical->id,
                'location_id' => $locStorefront->id,
                'purchase_price' => 3500,
                'price' => 5500,
                'stock' => 180,
                'min_stock' => 25,
                'description' => 'Taa ya mwangaza mweupe inayobana umeme',
            ],
            [
                'name' => 'Waya wa Umeme 1.5mm Single Core (Roll 100m)',
                'sku' => 'WAY-15MM-100',
                'category_id' => $catElectrical->id,
                'location_id' => $locStorefront->id,
                'purchase_price' => 38000,
                'price' => 48000,
                'stock' => 35,
                'min_stock' => 5,
                'description' => 'Rola ya mita 100 kwa ajili ya wiring za taa',
            ],
            [
                'name' => 'Soketi Double Panasonic 13A (Double Socket)',
                'sku' => 'SOK-PAN-DBL',
                'category_id' => $catElectrical->id,
                'location_id' => $locStorefront->id,
                'purchase_price' => 7500,
                'price' => 11000,
                'stock' => 65,
                'min_stock' => 10,
                'description' => 'Soketi ya ukutani yenye milango miwili ya pini 3',
            ],
            [
                'name' => 'Bomba la Maji PPR 25mm Class 20 (Hot & Cold)',
                'sku' => 'BOM-PPR-25M',
                'category_id' => $catPlumbing->id,
                'location_id' => $locMain->id,
                'purchase_price' => 12500,
                'price' => 16500,
                'stock' => 95,
                'min_stock' => 15,
                'description' => 'Bomba la maji ya moto na baridi urefu wa mita 4',
            ],
            [
                'name' => 'Koki ya Sink ya Chuma (Brass Basin Mixer Tap)',
                'sku' => 'KOK-SNK-BRS',
                'category_id' => $catPlumbing->id,
                'location_id' => $locStorefront->id,
                'purchase_price' => 32000,
                'price' => 45000,
                'stock' => 28,
                'min_stock' => 5,
                'description' => 'Koki ya kisasa ya kuoshea vyombo jikoni',
            ],
        ];

        foreach ($products as $pData) {
            Product::updateOrCreate(
                ['sku' => $pData['sku'], 'organization_id' => $orgId],
                array_merge($pData, [
                    'organization_id' => $orgId,
                    'is_active' => true,
                    'currency' => 'TZS',
                    'barcode' => 'TZ-' . rand(10000000, 99999999),
                ])
            );
        }

        $this->command->info('Tanzania Business Demo Data seeded successfully!');
    }
}
