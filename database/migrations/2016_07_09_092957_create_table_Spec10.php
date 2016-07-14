<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec10 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC10', function (Blueprint $table) {
            $table->increments('Spec10ID')->comment('尽职调查自增主键');
            $table->string('Informant',64)->comment('被调查方');
            $table->timestamps();
            // $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTYPE');
        });

        Schema::table('T_P_SPEC10', function ($table) {
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
        Schema::drop('T_P_SPEC10');
    }
}
