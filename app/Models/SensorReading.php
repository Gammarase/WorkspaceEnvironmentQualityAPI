<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'device_id',
        'temperature',
        'humidity',
        'tvoc_ppm',
        'light',
        'noise',
        'reading_timestamp',
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
            'device_id' => 'integer',
            'temperature' => 'decimal:2',
            'humidity' => 'decimal:2',
            'tvoc_ppm' => 'integer',
            'light' => 'integer',
            'noise' => 'integer',
            'reading_timestamp' => 'timestamp',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
