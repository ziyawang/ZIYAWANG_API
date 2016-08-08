<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableConfigItemtype extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_CONFIG_ITEMTYPE', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('MoudleID')->comment('新闻视频ID');
            $table->integer('TypeID')->comment('类型ID');
            $table->integer('Moudle')->comment('1、新闻，2、视频');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_CONFIG_ITEMTYPE');
    }
}
