<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsfeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('newsfeeds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('postid');
            $table->integer('visibilityType');
            $table->integer('createdBy');
            $table->json('feedJson');
            $table->integer('commentsCount')->default(0);
            $table->string('categoryJson');
            $table->string('hashJson');
            $table->json('reactionJson');
            $table->string('searchIndex');
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
        Schema::dropIfExists('newsfeeds');
    }
}
