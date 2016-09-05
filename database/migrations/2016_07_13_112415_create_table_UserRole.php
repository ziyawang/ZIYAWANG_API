<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUserRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_AS_USERROLE', function (Blueprint $table) {
            $table->increments('id')->comment('用户角色自增主键');
            $table->integer('UserID')->comment('用户ID');
            $table->integer('RoleID')->comment('角色ID');
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
        Schema::drop('T_AS_USERROLE');
    }
}
