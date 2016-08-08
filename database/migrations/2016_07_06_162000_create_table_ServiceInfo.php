<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableServiceInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_U_SERVICEINFO', function (Blueprint $table) {
            $table->increments('ServiceID')->comment('服务商自增主键');
            // $table->integer('UserID');
            $table->string('ServiceName',256)->comment('服务商名称');
            $table->string('ServiceIntroduction',512)->comment('服务商简介');
            $table->string('ServiceLocation',256)->comment('服务商所在地');
            $table->string('ServiceType',256)->comment('服务商类型');
            $table->string('ServiceLevel',256)->comment('服务商等级');
            $table->string('ConnectPerson',256)->comment('联系人姓名');
            $table->string('ConnectPhone',16)->comment('联系人电话');
            $table->string('ServiceArea',256)->comment('服务地区');
            $table->string('ConfirmationP1',512)->comment('资质认证资料');
            $table->string('ConfirmationP2',512)->comment('资质认证资料');
            $table->string('ConfirmationP3',152)->comment('资质认证资料');
            $table->integer('CollectionCount')->comment('搜藏次数');
            $table->integer('ViewCount')->comment('浏览次数');
            $table->string('Label',512)->comment('标签');
            $table->timestamps();
            // $table->foreign('UserID')->references('UserID')->on('users');
        });

        Schema::table('T_U_SERVICEINFO', function ($table) {
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
        Schema::drop('T_U_SERVICEINFO');
    }
}
