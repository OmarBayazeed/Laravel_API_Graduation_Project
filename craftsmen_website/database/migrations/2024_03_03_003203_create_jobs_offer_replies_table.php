<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsOfferRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs_offer_replies', function (Blueprint $table) {
            $table->id();
            $table->decimal('offered_price');
            $table->text('description')->nullable();
            $table->enum('type_of_pricing', ['contract','standard']);
            $table->unsignedBigInteger('job_offer_id');
            $table->unsignedBigInteger('craftsman_id');
            $table->timestamps();
            $table->foreign('job_offer_id')->references('id')->on('jobs_offers')->onDelete('cascade');
            $table->foreign('craftsman_id')->references('id')->on('craftsmen')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs_offer_replies');
    }
}
