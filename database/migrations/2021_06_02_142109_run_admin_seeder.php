<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class RunAdminSeeder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('admins')->truncate();
        DB::table('admins')->insert([
            'name' => 'Admin',
            'email' => 'linkme@thelinkmeapp.com',
            'password' => Hash::make('linkme@rene'),
        ]);
    }
   /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	DB::table('admins')->truncate();      
    }
}
