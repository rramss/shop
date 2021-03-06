<?php namespace Bedard\Shop\Updates;

use Illuminate\Database\Eloquent\Model;
use October\Rain\Database\Updates\Seeder;
use DB;

use Bedard\Shop\Models\Category;
use Bedard\Shop\Models\Product;
use Bedard\Shop\Models\Inventory;
use Bedard\Shop\Models\Coupon;

use Bedard\Shop\Models\Cart;
use Bedard\Shop\Models\CartItem;
use Bedard\Shop\Models\Order;
use Bedard\Shop\Models\Customer;
use Carbon\Carbon;

class TempSeeder extends Seeder {

    public function run()
    {
        //disable foreign key check for this connection before running seeders
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        /**
         * CATEGORY SEEDS
         */
        $i = 3;
        foreach (['Boards', 'Shirts', 'Hoodies', 'Stickers', 'Jackets'] as $category) {
            Category::create([
                'position'          => $i,
                'name'              => $category,
                'slug'              => strtolower($category),
                'is_visible'        => 1,
                'is_active'         => 1
            ]);
            $i++;
        }
        foreach (['Winter', 'DVDs'] as $category) {
            Category::create([
                'position'          => $i,
                'name'              => $category,
                'slug'              => strtolower($category),
                'is_visible'        => 0,
                'is_active'         => 1
            ]);
            $i++;
        }
        Category::create([
            'position'          => $i,
            'name'              => 'Hats',
            'slug'              => 'hats',
            'is_visible'        => 1,
            'is_active'         => 0
        ]);

        /**
         * PRODUCT SEEDS
         */
        $colors = ['red', 'blue', 'green', 'black', 'orange', 'yellow', 'purple', 'white'];
        $products = ['shirt', 'hat', 'board', 'sticker'];

        $seeds = [];
        foreach ($products as $product) foreach ($colors as $color) $seeds[] = $color.' '.$product;
        shuffle($seeds);

        Product::truncate();
        foreach ($seeds as $seed) {
            $product = Product::create([
                'name' => $seed,
                'slug' => str_replace(' ', '-', $seed),
                'full_price' => rand(10, 20),
                'description' => "Some awesome $seed... You should totaly buy it.",
                'is_active' => rand(0, 10) > 0 ? 1 : 0,
                'is_visible' => rand(0, 10) > 0 ? 1 : 0
            ]);
            if (strpos($seed, 'board') !== FALSE) $product->categories()->attach(3);
            elseif (strpos($seed, 'hat') !== FALSE) $product->categories()->attach(10);
            elseif (strpos($seed, 'shirt') !== FALSE) $product->categories()->attach(4);
            elseif (strpos($seed, 'sticker') !== FALSE) $product->categories()->attach(6);
            
            $small = Inventory::create([
                'product_id' => $product->id,
                'name' => 'Small',
                'quantity' => rand(0,2),
                'is_active' => rand(0, 10) > 0 ? 1 : 0
            ]);

            $medium = Inventory::create([
                'product_id' => $product->id,
                'name' => 'Medium',
                'quantity' => rand(0,2),
                'position' => 1,
                'is_active' => rand(0, 10) > 0 ? 1 : 0
            ]);

            $large = Inventory::create([
                'product_id' => $product->id,
                'name' => 'Large',
                'quantity' => rand(0,2),
                'position' => 2,
                'is_active' => rand(0, 10) > 0 ? 1 : 0
            ]);
        }

        /**
         * DISCOUNT SEEDS
         */
        // Demo category discount
        DB::table('bedard_shop_discounts')->insert([
            'name' => 'Category Discount',
            'amount' => rand(10,25),
            'is_percentage' => 1
        ]);
        DB::table('bedard_shop_discountables')->insert([
            'discount_id' => 1,
            'discountable_id' => rand(3, 6),
            'discountable_type' => 'Bedard\Shop\Models\Category'
        ]);

        DB::table('bedard_shop_discounts')->insert([
            'name' => 'Product Discount',
            'amount' => rand(5,8),
            'is_percentage' => 0
        ]);
        DB::table('bedard_shop_discountables')->insert([
            'discount_id' => 2,
            'discountable_id' => rand(1, 20),
            'discountable_type' => 'Bedard\Shop\Models\Product'
        ]);

        /**
         * PROMO CODE
         */
        Coupon::create([
            'name' => 'Foo',
            'message' => 'Thanks for entering "foo".',
            'amount' => rand(10, 20),
            'is_percentage' => 1,
            'cart_value' => rand(20, 50)
        ]);
        
        // Enable foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /**
         * Mock carts
         */
        $fnames = ['John', 'Mary', 'Alex', 'Mark', 'Sally'];
        $lnames = ['Smith', 'Johnson', 'Jones', 'Doe'];
        for ($i = 0; $i < 30; $i++) {
            $cart = Cart::create([]);
            $inventories = [];
            for ($j = 0; $j < rand(5, 15); $j++) $inventories[] = rand(1, 95);
            foreach ($inventories as $inventory) {
                $item = CartItem::firstOrCreate([
                    'cart_id' => $cart->id,
                    'inventory_id' => $inventory,
                    'quantity' => rand(1, 2)
                ]);
            }
            $first = $fnames[rand(0, 4)];
            $last = $lnames[rand(0,3)];
            $customer = Customer::firstOrCreate([
                'first_name' => $first,
                'last_name' => $last,
                'email' => strtolower("$first.$last@example.com")
            ]);
            $order = Order::create(['created_at' => Carbon::now()->subDays(rand(1, 50)) ]);
            $order->customer_id = $customer->id;
            $order->cart_id = $cart->id;
            $order->amount = $cart->total;
            $cart->markAsComplete($order);

            $order->shipping_address = [
                'first_name' => $first,
                'last_name' => $last,
                'address1' => '123 Foo Street',
                'city' => 'Beverly Hills',
                'state' => 'CA',
                'postcode' => '90210',
                'country' => 'US'
            ];
            $order->gateway = 'PayPal_Express';
            $order->gateway_code = 'FAKE-PAYPAL-ID';
            $order->save();
        }
        
    }

}