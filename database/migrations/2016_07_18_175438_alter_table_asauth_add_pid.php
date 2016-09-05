<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAsauthAddPid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_AS_AUTH', function (Blueprint $table) {
            $table->integer('PID')->comment('父ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_AS_AUTH', function (Blueprint $table) {
            $table->dropColumn('PID');
        });
    }
}
