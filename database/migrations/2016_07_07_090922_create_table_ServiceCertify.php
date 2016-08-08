<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableServiceCertify extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SERVICECERTIFY', function (Blueprint $table) {
            $table->increments('SerCerID')->comment('服务商审核自增主键');
            $table->string('State',32)->comment('审核状态');
            $table->dateTime('OperateTime')->comment('操作时间');
            $table->string('OperatePerson',32)->comment('操作人');
            $table->string('Remark',256)->comment('备注');
            $table->timestamps();
        });

        Schema::table('T_P_SERVICECERTIFY', function ($table) {
            $table->integer('ServiceID')->unsigned()->comment('外键服务商ID');
            $table->foreign('ServiceID')->references('ServiceID')->on('T_U_SERVICEINFO');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_P_SERVICECERTIFY');
    }
}
