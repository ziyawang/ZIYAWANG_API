<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCollection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_COLLECTION', function (Blueprint $table) {
            $table->increments('CollectionID')->comment('收藏自增主键');
            $table->integer('Type')->comment('收藏类型');
            $table->dateTime('CollectTime')->comment('收藏时间');
            $table->integer('ItemID')->comment('收藏项目的主键ID');
            $table->timestamps();
        });

        Schema::table('T_P_COLLECTION', function ($table) {
            $table->integer('UserID')->unsigned()->comment('外键用户ID');
            $table->foreign('UserID')->references('UserID')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_P_COLLECTION');
    }
}
