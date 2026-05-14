<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateAttendanceEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id');
            $table->date('date');
            $table->string('status');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->time('late')->nullable();
            $table->time('early_leaving')->nullable();
            $table->time('overtime')->nullable();
            $table->time('total_rest')->nullable();
            $table->decimal('latitudeIn', 10, 8)->nullable();
            $table->decimal('latitudeOut', 10, 8)->nullable();
            $table->decimal('longitudeIn', 11, 8)->nullable();
            $table->decimal('longitudeOut', 11, 8)->nullable();
            $table->string('locationIn');
            $table->string('locationOut');
            $table->integer('created_by');
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
        Schema::dropIfExists('attendance_employees');
    }
}
