<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRushProject extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_RUSHPROJECT', function (Blueprint $table) {
            $table->increments('RushProID')->comment('信息抢单自增主键');
            $table->dateTime('RushTime')->comment('抢单时间');
            $table->integer('CooperateFlag')->comment('信息确认合作标记');
            $table->timestamps();
        });

        Schema::table('T_P_RUSHPROJECT', function ($table) {
            $table->integer('ProjectID')->unsigned()->comment('外键信息ID');
            $table->foreign('ProjectID')->references('ProjectID')->on('T_P_PROJECTINFO');
            $table->integer('ServiceId')->unsigned()->comment('外键服务商ID');
            $table->foreign('ServiceId')->references('ServiceId')->on('T_U_SERVICEINFO');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_P_RUSHPROJECT');
    }
}
