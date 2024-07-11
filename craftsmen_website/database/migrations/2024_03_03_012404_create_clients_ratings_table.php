<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rating'); // 1 to 5
            $table->text('comment');
            $table->unsignedBigInteger('craftsman_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('done_job_id');
            $table->timestamps();
            $table->foreign('craftsman_id')->references('id')->on('craftsmen')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('done_job_id')->references('id')->on('craftsman_done_jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients_ratings');
    }
}
