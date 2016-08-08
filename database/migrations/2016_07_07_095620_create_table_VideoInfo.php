<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableVideoInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_V_VIDEOINFO', function (Blueprint $table) {
            $table->increments('VideoID')->comment('视频自增主键');
            $table->string('VideoTitle')->comment('视频标题');
            $table->string('VideoDes')->comment('视频简介');
            $table->string('VideoLabel')->comment('视频标签');
            $table->string('VideoLink')->comment('视频路径');            
            $table->dateTime('PublishTime')->comment('发布时间');
            $table->string('VideoAuthor',64)->comment('发布人');
            $table->integer('Order')->comment('排序');
            $table->integer('ViewCount')->comment('播放次数');
            $table->integer('CollectionCount')->comment('收藏次数');
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
        Schema::drop('T_V_VIDEOINFO');
    }
}
