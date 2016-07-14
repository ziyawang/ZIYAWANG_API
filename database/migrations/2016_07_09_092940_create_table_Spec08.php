<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec08 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC08', function (Blueprint $table) {
            $table->increments('Spec08ID')->comment('资产拍卖自增主键');
            $table->dateTime('Date')->comment('日期');
            $table->string('AssetType',64)->comment('类型');
            $table->timestamps();
            // $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTYPE');
        });

        Schema::table('T_P_SPEC08', function ($table) {
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
        Schema::drop('T_P_SPEC08');
    }
}
