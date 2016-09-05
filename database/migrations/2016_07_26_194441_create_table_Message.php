<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMessage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_M_MESSAGE', function (Blueprint $table) {
            $table->increments('MessageID');
            $table->integer('SendID')->comment('发送人ID');
            $table->integer('RecID')->comment('收信人ID');
            $table->integer('TextID')->comment('发送信息ID');
            $table->integer('Status')->comment('信息状态');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_M_MESSAGE');
    }
}
