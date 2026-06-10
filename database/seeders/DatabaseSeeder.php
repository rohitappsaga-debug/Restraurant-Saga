<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Table;
use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@restaurant.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'active' => true,
        ]);
        
        User::create([
            'name' => 'Test Waiter',
            'email' => 'waiter@restaurant.com',
            'password' => Hash::make('password'),
            'role' => UserRole::WAITER,
            'active' => true,
        ]);

        User::create([
            'name' => 'Kitchen Staff',
            'email' => 'kitchen@restaurant.com',
            'password' => Hash::make('password'),
            'role' => UserRole::KITCHEN,
            'active' => true,
        ]);

        User::create([
            'name' => 'Restaurant Manager',
            'email' => 'manager@restaurant.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'active' => true,
        ]);

        // 2. Seed Tables
        $tableData = [
            ['number' => 1, 'capacity' => 2, 'status' => 'free'],
            ['number' => 2, 'capacity' => 2, 'status' => 'free'],
            ['number' => 3, 'capacity' => 4, 'status' => 'occupied'],
            ['number' => 4, 'capacity' => 4, 'status' => 'free'],
            ['number' => 5, 'capacity' => 6, 'status' => 'cleaning'],
            ['number' => 6, 'capacity' => 6, 'status' => 'free'],
            ['number' => 7, 'capacity' => 8, 'status' => 'free'],
            ['number' => 8, 'capacity' => 2, 'status' => 'occupied'],
            ['number' => 9, 'capacity' => 4, 'status' => 'free'],
            ['number' => 10, 'capacity' => 4, 'status' => 'free'],
        ];

        foreach ($tableData as $t) {
            Table::create($t);
        }

        // 3. Seed Categories
        $catStarters = Category::create(['name' => 'Starters', 'description' => 'Appetizers and quick bites', 'is_active' => true]);
        $catMains = Category::create(['name' => 'Main Course', 'description' => 'Hearty meals and entrees', 'is_active' => true]);
        $catDrinks = Category::create(['name' => 'Beverages', 'description' => 'Refreshing drinks and sodas', 'is_active' => true]);
        $catDesserts = Category::create(['name' => 'Desserts', 'description' => 'Sweet treats to end your meal', 'is_active' => true]);

        // 4. Seed Menu Items
        $menuItems = [
            // Starters
            ['name' => 'Garlic Bread', 'description' => 'Toasted baguette with garlic butter', 'price' => 5.99, 'category' => 'Starters', 'category_id' => $catStarters->id, 'is_veg' => true, 'available' => true],
            ['name' => 'Crispy Calamari', 'description' => 'Fried squid rings with tartare sauce', 'price' => 9.50, 'category' => 'Starters', 'category_id' => $catStarters->id, 'is_veg' => false, 'available' => true],
            ['name' => 'Stuffed Mushrooms', 'description' => 'Cream cheese and herb stuffed mushrooms', 'price' => 7.50, 'category' => 'Starters', 'category_id' => $catStarters->id, 'is_veg' => true, 'available' => true],
            
            // Mains
            ['name' => 'Classic Cheeseburger', 'description' => 'Angus beef patty with cheddar cheese', 'price' => 12.99, 'category' => 'Main Course', 'category_id' => $catMains->id, 'is_veg' => false, 'available' => true],
            ['name' => 'Margherita Pizza', 'description' => 'Wood-fired crust with fresh mozzarella and basil', 'price' => 15.50, 'category' => 'Main Course', 'category_id' => $catMains->id, 'is_veg' => true, 'available' => true],
            ['name' => 'Grilled Salmon', 'description' => 'Fresh Atlantic salmon with asparagus', 'price' => 22.00, 'category' => 'Main Course', 'category_id' => $catMains->id, 'is_veg' => false, 'available' => true],
            ['name' => 'Mushroom Risotto', 'description' => 'Creamy arborio rice with wild mushrooms', 'price' => 16.50, 'category' => 'Main Course', 'category_id' => $catMains->id, 'is_veg' => true, 'available' => true],
            
            // Beverages
            ['name' => 'Fresh Lemonade', 'description' => 'House-made lemonade with mint', 'price' => 4.50, 'category' => 'Beverages', 'category_id' => $catDrinks->id, 'is_veg' => true, 'available' => true],
            ['name' => 'Iced Coffee', 'description' => 'Cold brew coffee with milk', 'price' => 5.00, 'category' => 'Beverages', 'category_id' => $catDrinks->id, 'is_veg' => true, 'available' => true],
            ['name' => 'Craft Beer', 'description' => 'Local IPA on tap', 'price' => 6.50, 'category' => 'Beverages', 'category_id' => $catDrinks->id, 'is_veg' => true, 'available' => true],

            // Desserts
            ['name' => 'Tiramisu', 'description' => 'Classic Italian coffee-flavored dessert', 'price' => 8.00, 'category' => 'Desserts', 'category_id' => $catDesserts->id, 'is_veg' => true, 'available' => true],
            ['name' => 'Cheesecake', 'description' => 'New York style vanilla cheesecake', 'price' => 7.50, 'category' => 'Desserts', 'category_id' => $catDesserts->id, 'is_veg' => true, 'available' => true],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }
    }
}
