<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRushprojectChangeServiceid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_RUSHPROJECT', function (Blueprint $table) {
            $table->renameColumn('ServiceId', 'ServiceID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_RUSHPROJECT', function (Blueprint $table) {
            $table->renameColumn('ServiceID', 'ServiceId');
        });
    }
}
