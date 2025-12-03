<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalWeatherData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'location',
        'latitude',
        'longitude',
        'outdoor_temperature',
        'outdoor_humidity',
        'outdoor_aqi',
        'outdoor_pm25',
        'outdoor_pm10',
        'weather_condition',
        'source',
        'fetched_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'outdoor_temperature' => 'decimal:2',
            'outdoor_humidity' => 'decimal:2',
            'outdoor_aqi' => 'integer',
            'outdoor_pm25' => 'decimal:2',
            'outdoor_pm10' => 'decimal:2',
            'fetched_at' => 'timestamp',
        ];
    }
}
