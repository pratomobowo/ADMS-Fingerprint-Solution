<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'sn',
        'table',
        'stamp',
        'employee_id',
        'timestamp',
        'status1',
        'status2',
        'status3',
        'status4',
        'status5',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'status1' => 'boolean',
        'status2' => 'boolean',
        'status3' => 'boolean',
        'status4' => 'boolean',
        'status5' => 'boolean',
    ];

    /**
     * Get device_sn attribute (alias for sn column)
     */
    public function getDeviceSnAttribute()
    {
        return $this->sn;
    }

    /**
     * Set device_sn attribute (alias for sn column)
     */
    public function setDeviceSnAttribute($value)
    {
        $this->attributes['sn'] = $value;
    }
}