<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveJobFinishedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('craftsman_jobs_finished', function (Blueprint $table) {
            $table->id();
            $table->string('CraftsmanStatus')->default('unfinished');
            $table->string('ClientStatus')->default('unfinished');
            $table->unsignedBigInteger('active_job_id');
            $table->unsignedBigInteger('craftsman_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();
            $table->foreign('active_job_id')->references('id')->on('craftsman_jobs')->onDelete('cascade');
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
        Schema::dropIfExists('_active_job_finshed');
    }
}
