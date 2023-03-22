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
        Schema::create('monitor_attrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('monitor_id');
            $table->string('name',255);
            $table->string('value',127);
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
        Schema::dropIfExists('monitor_attrs');
    }
};
