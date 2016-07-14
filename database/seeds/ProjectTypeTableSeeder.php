<?php

use Illuminate\Database\Seeder;

class ProjectTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '资产包转让',
            'TableName' => 'T_P_SPEC01'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '委外催收',
            'TableName' => 'T_P_SPEC02'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '法律服务',
            'TableName' => 'T_P_SPEC03'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '商业保理',
            'TableName' => 'T_P_SPEC04'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '典当担保',
            'TableName' => 'T_P_SPEC05'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '融资借贷',
            'TableName' => 'T_P_SPEC06'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '资金过桥',
            'TableName' => 'T_P_SPEC07'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '资产拍卖',
            'TableName' => 'T_P_SPEC08'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '悬赏信息',
            'TableName' => 'T_P_SPEC09'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '尽职调查',
            'TableName' => 'T_P_SPEC10'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '评估审计',
            'TableName' => 'T_P_SPEC11'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '固产转让',
            'TableName' => 'T_P_SPEC12'
        ]);
        DB::table('T_P_PROJECTTYPE')->insert([
            'TypeName' => '资产求购',
            'TableName' => 'T_P_SPEC13'
        ]);
    }
}
