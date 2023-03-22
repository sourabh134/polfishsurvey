<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demographic_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('monitor_id');
            $table->text('data');
            $table->tinyInteger('job_status')->length(1)->default(1)->unsigned()->comment('0 = UNKNOWN, 1 = data received, attribute update not completed, 2 = attribute update completed');
            $table->timestamp('job_finished_at')->nullable();
            $table->timestamps();
            $table->foreign('monitor_id')
                ->references('monitor_id')
                ->on('monitors')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('demographic_histories');
    }
};
