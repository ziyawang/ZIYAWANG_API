<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVideoinfoAddVideologo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_V_VIDEOINFO', function (Blueprint $table) {
             $table->string('VideoLogo',256)->comment('视频图片');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_V_VIDEOINFO', function (Blueprint $table) {
             $table->dropColumn('Flag');
        });
    }
}
