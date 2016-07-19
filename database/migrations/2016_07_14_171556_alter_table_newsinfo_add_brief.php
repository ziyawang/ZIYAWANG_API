<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNewsinfoAddBrief extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_N_NEWSINFO', function (Blueprint $table) {
            $table->text('Brief')->comment('摘要');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_N_NEWSINFO', function (Blueprint $table) {
             $table->dropColumn('Brief');
        });
    }
}
