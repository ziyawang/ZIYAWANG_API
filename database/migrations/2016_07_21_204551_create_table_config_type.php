<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableConfigType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_CONFIG_TYPE', function (Blueprint $table) {
            $table->increments('id');
            $table->string('TypeName')->comment('类型名称');
            $table->integer('Module')->comment('1、新闻，2、视频');
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
        Schema::drop('T_CONFIG_TYPE');
    }
}
