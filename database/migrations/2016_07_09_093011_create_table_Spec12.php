<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSpec12 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_SPEC12', function (Blueprint $table) {
            $table->increments('Spec12ID')->comment('固产转让自增主键');
            $table->string('AssetType',64)->comment('类型');
            $table->decimal('TransferMoney')->comment('转让价');
            $table->timestamps();
            // $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTYPE');
        });

        Schema::table('T_P_SPEC12', function ($table) {
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
        Schema::drop('T_P_SPEC12');
    }
}
