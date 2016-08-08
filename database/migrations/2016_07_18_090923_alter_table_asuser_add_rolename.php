<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAsuserAddRolename extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_AS_USER', function (Blueprint $table) {
            $table->integer('RoleID')->comment('角色ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_AS_USER', function (Blueprint $table) {
            $table->dropColumn('RoleID');
        });
    }
}
