<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNewsinfoAddFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_N_NEWSINFO', function (Blueprint $table) {
            $table->integer('Flag')->comment('状态，0-未发布，1-已发布，2-已删除');
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
            $table->dropColumn('Flag');
        });
    }
}
