<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRoleauthChangeAuthid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_AS_ROLEAUTH', function (Blueprint $table) {
            // $table->string('AuthID',256)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_AS_ROLEAUTH', function (Blueprint $table) {
            // $table->integer('AuthID')->change();
        });
    }
}
