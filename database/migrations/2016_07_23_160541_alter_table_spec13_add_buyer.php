<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSpec13AddBuyer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_SPEC13', function (Blueprint $table) {
            $table->string('Buyer',128)->comment('求购方');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_SPEC13', function (Blueprint $table) {
            $table->dropColumn('Buyer');
        });
    }
}
