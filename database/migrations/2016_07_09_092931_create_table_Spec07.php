<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec07 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC07', function (Blueprint $table) {
            $table->increments('Spec07ID')->comment('资金过桥自增主键');
            $table->decimal('TotalMoney')->comment('金额');
            $table->string('ServiceLife',64)->comment('使用期限');
            $table->timestamps();
            // $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTYPE');
        });

        Schema::table('T_P_SPEC07', function ($table) {
            $table->integer('TypeID')->unsigned()->comment('外键类型ID');
            $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTTYPE');
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
        Schema::drop('T_P_SPEC07');
    }
}
