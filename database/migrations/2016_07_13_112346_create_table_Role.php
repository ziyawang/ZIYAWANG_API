<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_AS_ROLE', function (Blueprint $table) {
            $table->increments('id')->comment('角色自增主键');
            $table->string('RoleName',32)->comment('角色名称');
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
        Schema::drop('T_AS_ROLE');
    }
}
