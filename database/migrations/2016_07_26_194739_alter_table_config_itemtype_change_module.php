<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableConfigItemtypeChangeModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('T_CONFIG_ITEMTYPE', function (Blueprint $table) {
            $table->renameColumn('MoudleID', 'ModuleID');
            $table->renameColumn('Moudle', 'Module');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('T_CONFIG_ITEMTYPE', function (Blueprint $table) {
            $table->renameColumn('ModuleID', 'MoudleID');
            $table->renameColumn('Module', 'Moudle');
        });
    }
}
