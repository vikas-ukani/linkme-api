<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostcommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postcomments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('postid');
            $table->longtext('comment')->nullable();
            $table->integer('commentBy');
            $table->integer('reactionCount')->default(0);
            $table->integer('commentparentId')->nullable();
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
        Schema::dropIfExists('postcomments');
    }
}
