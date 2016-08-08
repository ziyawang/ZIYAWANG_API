<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProjectCertify extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_PROJECTCERTIFY', function (Blueprint $table) {
            $table->increments('ProCerID')->comment('信息审核自增主键');
            $table->string('State')->comment('审核状态');
            $table->dateTime('CertifyTime')->comment('审核时间');
            $table->string('Remark',512)->comment('备注');
            $table->timestamps();
        });

        Schema::table('T_P_PROJECTCERTIFY', function ($table) {
            $table->integer('ProjectID')->unsigned()->comment('外键信息ID');
            $table->foreign('ProjectID')->references('ProjectID')->on('T_P_PROJECTINFO');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_P_PROJECTCERTIFY');
    }
}
