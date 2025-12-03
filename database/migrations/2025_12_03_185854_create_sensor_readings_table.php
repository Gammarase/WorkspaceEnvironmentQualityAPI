<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained();
            $table->decimal('temperature', 5, 2);
            $table->decimal('humidity', 5, 2);
            $table->unsignedInteger('tvoc_ppm')->nullable();
            $table->unsignedInteger('light');
            $table->unsignedInteger('noise');
            $table->timestamp('reading_timestamp');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
