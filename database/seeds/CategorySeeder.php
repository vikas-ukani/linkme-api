<?php

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (DB::table('categories')->get()->count() == 0) {

            DB::table('categories')->insert([

                [
                    'category_name' => 'Hair Stylist',
                    'catimg' => '1607518723.jpg'
                ],
                [
                    'category_name' => 'Barber',
                    'catimg' => '1607518747.png'
                ],
                [
                    'category_name' => 'Makeup Artist',
                    'catimg' => '1607518772.jpg'
                ]

            ]);
        }
    }
}
