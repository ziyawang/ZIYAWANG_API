<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSpec10AddAssetType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_P_SPEC10', function (Blueprint $table) {
            $table->string('AssetType',128)->comment('类型');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_P_SPEC10', function (Blueprint $table) {
            $table->dropColumn('AssetType');
        });
    }
}
