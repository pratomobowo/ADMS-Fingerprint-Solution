<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_config_id',
        'attendance_id',
        'status',
        'http_status_code',
        'response_body',
        'error_message',
        'attempt_number',
        'delivered_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'attempt_number' => 'integer',
        'http_status_code' => 'integer',
    ];

    public function webhookConfig()
    {
        return $this->belongsTo(WebhookConfig::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
