<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with production-ready default roles, users, tables, categories, and menu items.
     */
    public function run(): void
    {
        // 1. Seed Roles
        $rolesData = [
            Role::ADMIN => 'System Administrator',
            Role::MANAGER => 'Duty Manager',
            Role::STAFF => 'Sales Assistant / Waitstaff',
            Role::CASHIER => 'Cashier & Billing Officer',
            Role::KITCHEN => 'Executive Chef / KDS Operator',
        ];

        $roles = [];
        foreach ($rolesData as $name => $label) {
            $roles[$name] = Role::updateOrCreate(
                ['name' => $name],
                ['label' => $label]
            );
        }

        // 2. Seed Default Users (with hashed PIN codes for Admin & Manager)
        User::updateOrCreate(
            ['email' => 'admin@hotel.local'],
            [
                'role_id' => $roles[Role::ADMIN]->id,
                'name' => 'Nuwan Wijesinghe (Admin)',
                'password' => Hash::make('password'),
                'pin_code' => Hash::make('9999'),
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@hotel.local'],
            [
                'role_id' => $roles[Role::MANAGER]->id,
                'name' => 'Dilshan Fernando (Manager)',
                'password' => Hash::make('password'),
                'pin_code' => Hash::make('1234'),
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@hotel.local'],
            [
                'role_id' => $roles[Role::STAFF]->id,
                'name' => 'Kavindu Silva (Sales Assistant)',
                'password' => Hash::make('password'),
                'pin_code' => null,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'cashier@hotel.local'],
            [
                'role_id' => $roles[Role::CASHIER]->id,
                'name' => 'Nadeesha Perera (Cashier)',
                'password' => Hash::make('password'),
                'pin_code' => null,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kitchen@hotel.local'],
            [
                'role_id' => $roles[Role::KITCHEN]->id,
                'name' => 'Chef Rohan De Silva (Kitchen)',
                'password' => Hash::make('password'),
                'pin_code' => null,
                'is_active' => true,
            ]
        );

        // 3. Seed Restaurant Tables with UUID QR Tokens
        for ($i = 1; $i <= 8; $i++) {
            $tableNumber = sprintf('T-%02d', $i);
            RestaurantTable::firstOrCreate(
                ['table_number' => $tableNumber],
                [
                    'qr_token' => (string) Str::uuid(),
                    'status' => 'vacant',
                ]
            );
        }

        // 4. Seed Menu Categories (Starters, Mains, Desserts, Beverages)
        $categoriesConfig = [
            [
                'name' => 'Starters',
                'slug' => 'starters',
                'station' => 'kitchen',
                'display_order' => 1,
                'items' => [
                    [
                        'name' => 'Negombo Crispy Calamari Rings',
                        'description' => 'Golden fried lagoon calamari tossed with curry leaves, crushed black pepper, and lime aioli.',
                        'price' => 1850.00,
                        'cost_price' => 680.00,
                        'current_stock' => 40,
                        'is_vat_exempt' => false,
                    ],
                    [
                        'name' => 'Roasted Pumpkin & Coconut Veloute',
                        'description' => 'Silky heirloom pumpkin soup infused with toasted cumin and Ceylon cinnamon croutons.',
                        'price' => 1250.00,
                        'cost_price' => 390.00,
                        'current_stock' => -1,
                        'is_vat_exempt' => false,
                    ],
                    [
                        'name' => 'Mutton Rolls with Kochchi Dip (3 Pcs)',
                        'description' => 'Traditional spiced slow-cooked mutton and potato croquettes crumb-fried to perfection.',
                        'price' => 1450.00,
                        'cost_price' => 520.00,
                        'current_stock' => 25,
                        'is_vat_exempt' => false,
                    ],
                ],
            ],
            [
                'name' => 'Mains',
                'slug' => 'mains',
                'station' => 'kitchen',
                'display_order' => 2,
                'items' => [
                    [
                        'name' => 'Jaffna Lagoon Crab Curry & Roast Paan',
                        'description' => 'Signature mud crab simmered in roasted northern spices, drumstick leaves, and thick coconut milk.',
                        'price' => 4600.00,
                        'cost_price' => 1950.00,
                        'current_stock' => 15,
                        'is_vat_exempt' => false,
                    ],
                    [
                        'name' => 'Odara Signature Lamprais',
                        'description' => 'Dutch-Burgher banana-leaf baked samba rice with mixed meat curry, seeni sambol, frikkadels, and ash plantain.',
                        'price' => 2950.00,
                        'cost_price' => 1100.00,
                        'current_stock' => 30,
                        'is_vat_exempt' => false,
                    ],
                    [
                        'name' => 'Chargrilled Mahi-Mahi Fillet',
                        'description' => 'Southern coast mahi-mahi with garlic herb butter, whipped potato mash, and grilled asparagus.',
                        'price' => 3400.00,
                        'cost_price' => 1320.00,
                        'current_stock' => 20,
                        'is_vat_exempt' => false,
                    ],
                ],
            ],
            [
                'name' => 'Desserts',
                'slug' => 'desserts',
                'station' => 'bar',
                'display_order' => 3,
                'items' => [
                    [
                        'name' => 'Kithul Jaggery Watalappan',
                        'description' => 'Steamed spiced coconut custard sweetened with pure kithul jaggery and roasted cashews.',
                        'price' => 1150.00,
                        'cost_price' => 340.00,
                        'current_stock' => 35,
                        'is_vat_exempt' => false,
                    ],
                    [
                        'name' => 'Warm Dark Chocolate Lava Cake',
                        'description' => '70% single-origin chocolate fondant served with Madagascar vanilla bean gelato.',
                        'price' => 1550.00,
                        'cost_price' => 490.00,
                        'current_stock' => 20,
                        'is_vat_exempt' => false,
                    ],
                ],
            ],
            [
                'name' => 'Beverages',
                'slug' => 'beverages',
                'station' => 'bar',
                'display_order' => 4,
                'items' => [
                    [
                        'name' => 'Chilled King Coconut (Thambili)',
                        'description' => 'Freshly harvested golden Sri Lankan king coconut served chilled (VAT Exempt natural produce).',
                        'price' => 650.00,
                        'cost_price' => 180.00,
                        'current_stock' => -1,
                        'is_vat_exempt' => true,
                    ],
                    [
                        'name' => 'Ceylon Artisanal Iced Peach Tea',
                        'description' => 'Nuwara Eliya high-grown broken orange pekoe brewed cold with white peach and mint.',
                        'price' => 890.00,
                        'cost_price' => 210.00,
                        'current_stock' => -1,
                        'is_vat_exempt' => false,
                    ],
                    [
                        'name' => 'Knuckles Mountain Spring Water (750ml)',
                        'description' => 'Still natural mineral water bottled at the source.',
                        'price' => 450.00,
                        'cost_price' => 140.00,
                        'current_stock' => 100,
                        'is_vat_exempt' => true,
                    ],
                ],
            ],
        ];

        foreach ($categoriesConfig as $catData) {
            $category = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'station' => $catData['station'],
                    'display_order' => $catData['display_order'],
                    'is_active' => true,
                ]
            );

            foreach ($catData['items'] as $itemData) {
                MenuItem::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $itemData['name'],
                    ],
                    [
                        'description' => $itemData['description'],
                        'price' => $itemData['price'],
                        'cost_price' => $itemData['cost_price'],
                        'is_available' => true,
                        'is_vat_exempt' => $itemData['is_vat_exempt'],
                        'current_stock' => $itemData['current_stock'],
                    ]
                );
            }
        }
    }
}
