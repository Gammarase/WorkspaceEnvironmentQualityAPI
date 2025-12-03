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

        Schema::create('external_weather_data', function (Blueprint $table) {
            $table->id();
            $table->string('location', 255);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('outdoor_temperature', 5, 2)->nullable();
            $table->decimal('outdoor_humidity', 5, 2)->nullable();
            $table->unsignedInteger('outdoor_aqi')->nullable();
            $table->decimal('outdoor_pm25', 6, 2)->nullable();
            $table->decimal('outdoor_pm10', 6, 2)->nullable();
            $table->string('weather_condition', 100)->nullable();
            $table->enum('source', ['openweathermap', 'airvisual', 'iqair', 'weatherapi']);
            $table->timestamp('fetched_at');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_weather_data');
    }
};
