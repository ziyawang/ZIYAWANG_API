<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec02 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC02', function (Blueprint $table) {
            $table->increments('Spec02ID')->comment('委外催收自增主键');
            $table->decimal('TotalMoney')->comment('金额');
            $table->string('Status')->comment('状态');
            $table->string('AssetType',64)->comment('类型');
            $table->string('Rate',64)->comment('佣金比例');
            $table->timestamps();
        });

        Schema::table('T_P_SPEC02', function ($table) {
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
        Schema::drop('T_P_SPEC02');
    }
}
