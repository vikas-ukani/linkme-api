<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fname');
            $table->string('lname');
            $table->string('email');
            $table->integer('user_type')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->bigInteger('phone');
            $table->longtext('address');
            $table->string('city');
            $table->string('state');
            $table->string('zipcode');
            $table->string('category')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->longtext('bio')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
