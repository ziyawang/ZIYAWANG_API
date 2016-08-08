<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec11 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC11', function (Blueprint $table) {
            $table->increments('Spec11ID')->comment('评估审计');
            $table->string('Requriement',64)->comment('需求');
            $table->string('AssetType',64)->comment('类型');
            $table->timestamps();
            // $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTYPE');
        });

        Schema::table('T_P_SPEC11', function ($table) {
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
        Schema::drop('T_P_SPEC11');
    }
}
