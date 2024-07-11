<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoneJobsRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('craftsman_done_jobs_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rating'); // 1 to 5
            $table->text('comment');
            $table->unsignedBigInteger('craftsmanDoneJob_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();
            $table->foreign('craftsmanDoneJob_id')->references('id')->on('craftsman_done_jobs')->onDelete('cascade');
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
        Schema::dropIfExists('done_jobs_ratings');
    }
}
