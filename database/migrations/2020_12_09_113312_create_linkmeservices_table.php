<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLinkmeservicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('linkmeservices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('provider_id');
            $table->string('title');
            $table->string('category');
            $table->string('duration');
            $table->string('price');
            $table->string('before_24_cancellation');
            $table->string('after_24_cancellation');
            $table->longtext('description');
            $table->string('service_img')->nullable();
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
        Schema::dropIfExists('linkmeservices');
    }
}
