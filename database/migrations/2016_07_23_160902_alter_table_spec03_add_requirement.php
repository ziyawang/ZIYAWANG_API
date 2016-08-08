<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSpec03AddRequirement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_SPEC03', function (Blueprint $table) {
            $table->string('Requirement',128)->comment('需求');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_SPEC03', function (Blueprint $table) {
            $table->dropColumn('Requirement');
        });
    }
}
