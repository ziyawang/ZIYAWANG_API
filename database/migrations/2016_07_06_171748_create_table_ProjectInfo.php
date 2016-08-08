<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProjectInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_PROJECTINFO', function (Blueprint $table) {
            $table->increments('ProjectID')->comment('信息自增主键');
            $table->integer('ServiceID')->comment('服务商ID');
            $table->string('ProArea',32)->comment('信息所处地区');
            $table->text('WordDes')->comment('文字描述');
            $table->string('VoiceDes',512)->comment('音频路径');
            $table->string('PictureDes',512)->comment('图片路径');
            $table->dateTime('CertifyTime')->comment('审核时间');
            $table->dateTime('PublishTime')->comment('发布时间');
            $table->integer('DoneState')->comment('完成状态');
            $table->integer('CertifyState')->comment('审核状态');
            $table->integer('PublishState')->comment('发布状态');
            $table->integer('ViewCount')->comment('浏览次数');
            $table->integer('CollectionCount')->comment('收藏次数');
            $table->integer('DeleteFlag')->comment('删除标记');
            $table->string('ProLabel',512)->comment('标签');
            $table->timestamps();
        });

        Schema::table('T_P_PROJECTINFO', function ($table) {
            $table->integer('UserID')->unsigned()->comment('外键用户ID');
            $table->foreign('UserID')->references('UserID')->on('users');
            $table->integer('TypeID')->unsigned()->comment('外键类型ID');
            $table->foreign('TypeID')->references('TypeID')->on('T_P_PROJECTTYPE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_P_PROJECTINFO');
    }
}
