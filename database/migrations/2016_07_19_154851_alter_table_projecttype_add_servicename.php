<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableProjecttypeAddServicename extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_PROJECTTYPE', function (Blueprint $table) {
            $table->string('SerName',64)->comment('服务商类型名称');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_PROJECTTYPE', function (Blueprint $table) {
            $table->dropColumn('SerName');
        });
    }
}
