<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSysMessage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_M_SYSMESSAGE', function (Blueprint $table) {
            $table->increments('SysID');
            $table->integer('CustomerID');
            $table->integer('MessageID');
            $table->integer('SysStatus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_M_SYSMESSAGE');
    }
}
