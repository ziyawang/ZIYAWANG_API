<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAsauthChangeIdToAuthId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_AS_AUTH', function (Blueprint $table) {
            $table->renameColumn('id', 'Auth_ID');
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
            $table->renameColumn('Auth_ID','id');
        });
    }
}
