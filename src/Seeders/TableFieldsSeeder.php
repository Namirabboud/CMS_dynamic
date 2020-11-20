<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Eve\Dynamic\Models\TableField;
use Illuminate\Support\Facades\DB;


class TableFieldsSeeder extends Seeder
{

    public $tables = ['table_fields'];

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach($this->tables as $table){
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');



        // $this->call(UsersTableSeeder::class);
    }
}
