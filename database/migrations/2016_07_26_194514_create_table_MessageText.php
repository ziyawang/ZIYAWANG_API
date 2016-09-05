<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMessageText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_M_MESSAGETEXT', function (Blueprint $table) {
            $table->increments('TextID');
            $table->string('Title',256)->comment('信息标题');
            $table->text('Text')->comment('信息内容');
            $table->dateTime('Time')->comment('发送时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('T_M_MESSAGETEXT');
    }
}
