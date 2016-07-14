<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('userid')->comment('用户自增主键');
            $table->string('username')->comment('用户名');
            $table->string('phonenumber',16)->unique()->comment('用户手机');
            $table->string('password', 60)->comment('用户密码');
            $table->string('logintoken', 256)->comment('登录token');
            $table->string('email', 64)->comment('用户邮箱');
            $table->string('truename', 64)->comment('用户真实姓名');
            $table->string('idcard', 32)->comment('用户身份证');
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
        Schema::drop('users');
    }
}
