<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec01 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC01', function (Blueprint $table) {
            $table->increments('Spec01ID')->comment('资产包转让自增主键');
            $table->string('FromWhere',64)->comment('来源');
            $table->decimal('TotalMoney')->comment('总金额');
            $table->decimal('TransferMoney')->comment('转让价');
            $table->string('AssetType',64)->comment('资产包类型');
            $table->string('AssetList',512)->comment('资产包清单路径');
            $table->timestamps();
        });

        Schema::table('T_P_SPEC01', function ($table) {
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
        Schema::drop('T_P_SPEC01');
    }
}
