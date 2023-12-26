<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInHomeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_in_home_service')->default(false)->comment('SP in Home Service')->after('preference_location');
            $table->string('service_location_lat', 20)->nullable()->comment('SP Service Location|Filter')->after('preference_location');
            $table->string('service_location_long', 20)->nullable()->comment('SP Service Location|Filter')->after('preference_location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
