<?php namespace Bedard\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('bedard_shop_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 64)->nullable();
            $table->string('slug', 64)->unique()->nullable();
            $table->string('description')->nullable();
            $table->integer('position')->unsigned();
            $table->string('pseudo', 4)->nullable();
            $table->boolean('is_visible')->unsigned();
            $table->boolean('is_active')->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bedard_shop_categories');
    }

}