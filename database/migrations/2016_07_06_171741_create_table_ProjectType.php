<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProjectType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('T_P_PROJECTTYPE', function (Blueprint $table) {
            $table->increments('TypeID')->comment('类型自增主键');
            $table->string('TypeName',64)->comment('类型名称');
            $table->string('TableName',64)->comment('对应类型属性表的表名');
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
        Schema::drop('T_P_PROJECTTYPE');
    }
}
