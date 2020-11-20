<?php

use Illuminate\Database\Seeder;
use Eve\Dynamic\Models\TableField;

class DatabaseSeeder extends Seeder
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
