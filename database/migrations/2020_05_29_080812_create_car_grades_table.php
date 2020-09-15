<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_grades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('car_model_id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('capacity')->nullable();
            $table->unsignedSmallInteger('length')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->date('start_at')->nullable();
            $table->date('end_at')->nullable();
            $table->string('body_type', 20)->nullable();
            $table->text('description')->nullable();
            $table->text('photo_front_url')->nullable();
            $table->text('photo_front_caption')->nullable();
            $table->text('photo_rear_url')->nullable();
            $table->text('photo_rear_caption')->nullable();
            $table->text('photo_dashboard_url')->nullable();
            $table->text('photo_dashboard_caption')->nullable();
            $table->text('url')->nullable();
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
        Schema::dropIfExists('car_grades');
    }
}
