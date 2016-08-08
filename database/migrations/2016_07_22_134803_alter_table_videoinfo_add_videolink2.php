<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVideoinfoAddVideolink2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_V_VIDEOINFO', function (Blueprint $table) {
            $table->string('VideoLink2',256)->comment('移动端视频播放地址');
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
            $table->dropColumn('VideoLink2');
        });
    }
}
