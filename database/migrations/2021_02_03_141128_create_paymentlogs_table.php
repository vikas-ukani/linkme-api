<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paymentlogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('bookingId');
            $table->integer('CustomerId');
            $table->string('bookingamount');
            $table->string('fees_collected');
            $table->string('paid_out');
            $table->string('tip');
            $table->string('chargeId');
            $table->string('paymentstatus')->default('hold');
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
        Schema::dropIfExists('paymentlogs');
    }
}
