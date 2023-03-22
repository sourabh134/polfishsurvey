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
        Schema::create('survey_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('monitor_id');
            $table->bigInteger('survey_id')->length(20)->unsigned();
            $table->string('survey_class',128);
            $table->tinyInteger('survey_ir')->length(4)->unsigned();
            $table->tinyInteger('survey_loi')->length(4)->unsigned();
            $table->float('survey_cpa',6,3);
            $table->string('reward_name',128);
            $table->float('reward_value',6,3);
            $table->text('survey_link');
            $table->string('survey_lang',128);
            $table->string('request_uuid',255)->nullable();
            $table->string('signature',255)->nullable();
            $table->string('click_id',255)->nullable();
            $table->text('redirect_url_raw')->nullable();
            $table->text('callback_url_raw')->nullable();
            $table->tinyInteger('survey_status')->length(4)->unsigned()->default(0)->comment('0 = Did not participate or answer is not complete, 1 = Complete, 2 = Failed, 3 = eligible, 255 = UNKNOWN');
            $table->text('term_reason')->nullable();
            $table->tinyInteger('given_point_status')->length(1)->unsigned()->default(0)->comment('-1 = grant failed, 0 = not granted, 1 = granted successfully, 2 = not granted');
            $table->timestamp('given_point_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('start_datetime')->useCurrent();
            $table->timestamp('end_datetime')->nullable();
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
        Schema::dropIfExists('survey_histories');
    }
};
