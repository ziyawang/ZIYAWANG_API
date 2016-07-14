<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_AS_USER', function (Blueprint $table) {
            $table->increments('id')->comment('用户自增主键');
            $table->string('Name',32)->comment('姓名');
            $table->string('Email',64)->comment('邮箱');
            $table->string('PhoneNumber',11)->comment('手机号');
            $table->string('PassWord',256)->comment('密码');
            $table->string('Department',32)->comment('部门');
            $table->integer('Status')->comment('状态');
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
        Schema::drop('T_AS_USER');
    }
}
