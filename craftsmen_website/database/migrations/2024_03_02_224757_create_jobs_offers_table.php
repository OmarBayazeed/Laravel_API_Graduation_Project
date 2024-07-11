<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('client_price')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('city');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('craft_id');
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
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
        Schema::dropIfExists('jobs_offers');
    }
}
