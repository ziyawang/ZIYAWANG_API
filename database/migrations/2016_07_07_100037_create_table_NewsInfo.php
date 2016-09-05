<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableNewsInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_N_NEWSINFO', function (Blueprint $table) {
            $table->increments('NewsID')->comment('新闻资讯自增主键');
            $table->string('NewsTitle',512)->comment('新闻标题');
            $table->text('NewsContent')->comment('新闻内容');
            $table->string('NewsLogo',512)->comment('新闻Logo');
            $table->string('NewsLabel',512)->comment('新闻标签');
            $table->dateTime('PublishTime')->comment('发布时间');
            $table->string('NewsAuthor',64)->comment('发布人');
            $table->integer('Order')->comment('排序');
            $table->integer('ViewCount')->comment('浏览次数');
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
        Schema::drop('T_N_NEWSINFO');
    }
}
