<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAuth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_AS_AUTH', function (Blueprint $table) {
            $table->increments('id')->comment('权限自增主键');
            $table->string('AuthName',32)->comment('权限名称');
            $table->integer('Level')->comment('权限级别');
            $table->string('Path',128)->comment('权限路由');
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
        Schema::drop('T_AS_AUTH');
    }
}
