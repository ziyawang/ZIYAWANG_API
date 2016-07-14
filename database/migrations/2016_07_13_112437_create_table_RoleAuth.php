<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRoleAuth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_AS_ROLEAUTH', function (Blueprint $table) {
            $table->increments('id')->comment('用户自增主键');
            $table->integer('RoleID')->comment('角色ID');
            $table->integer('AuthID')->comment('权限ID');
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
        Schema::drop('T_AS_ROLEAUTH');
    }
}
