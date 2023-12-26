<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAvgRatingColumnTypeLinkmeServices extends Migration
{
/**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::table('linkmeservices', function (Blueprint $table) {
            $table->decimal('avg_rating',5,2)->change();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('linkmeservices', function (Blueprint $table) {
            $table->integer('avg_rating')->change();
        });

    }
}
