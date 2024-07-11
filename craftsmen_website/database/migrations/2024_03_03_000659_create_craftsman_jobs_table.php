<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCraftsmanJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('craftsman_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description');
            $table->string('status')->default('unfinished');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->decimal('price');
            $table->string('city');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('type_of_pricing')->nullable();
            $table->unsignedBigInteger('craftsman_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();
            $table->foreign('craftsman_id')->references('id')->on('craftsmen')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('craftsman_jobs');
    }
}
