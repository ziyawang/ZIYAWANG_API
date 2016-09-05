<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSpec14ChangePkid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_SPEC14', function (Blueprint $table) {
            $table->renameColumn('Spec13ID', 'Spec14ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_SPEC14', function (Blueprint $table) {
            $table->renameColumn('Spec14ID', 'Spec13ID');
        });
    }
}
