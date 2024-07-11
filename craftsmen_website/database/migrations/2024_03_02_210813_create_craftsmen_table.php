<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCraftsmenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('craftsmen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('address')->nullable();
            $table->enum('status', ['free','busy'])->nullable();
            $table->enum('availability', ['available','unavailable'])->nullable();
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('craft_id')->nullable();
            $table->string('social_id')->nullable();
            $table->string('social_type')->nullable();
            $table->timestamps();
            $table->foreign('craft_id')->references('id')->on('crafts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('craftmen');
    }
}
