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
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('monitor_id');
            $table->float('survey_cpa',6,3);
            $table->string('request_uuid',128);            
            $table->timestamp('timestamp');
            $table->string('tx_id',128);
            $table->string('signature',128);
            $table->string('click_id',128);
            $table->string('action',128);
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
        Schema::dropIfExists('reconciliations');
    }
};
