<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('table_fields', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('model_name');
            $table->index('model_name');

            $table->string('field_name')->nullable();
            $table->string('field_type')->nullable();


            $table->string('foreign_table')->nullable();
            $table->index('foreign_table');
            $table->string('foreign_field')->nullable();
            $table->string('other_field')->nullable();

            $table->text('options')->nullable();


            $table->integer('order')->nullable();
            $table->index('order');

            $table->boolean('mandatory')->default(false);

            $table->string('class')->nullable();

            $table->boolean('is_visible')->default(true);
            $table->index('is_visible');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_fields');
    }
}
