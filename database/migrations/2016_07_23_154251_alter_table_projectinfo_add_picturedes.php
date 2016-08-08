<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableProjectinfoAddPicturedes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_PROJECTINFO', function (Blueprint $table) {
            $table->renameColumn('PictureDes', 'PictureDes1');
            $table->string('PictureDes2',256);
            $table->string('PictureDes3',256);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_PROJECTINFO', function (Blueprint $table) {
            $table->renameColumn('PictureDes1', 'PictureDes');
            $table->dropColumn('PictureDes2');
            $table->dropColumn('PictureDes3');
        });
    }
}
