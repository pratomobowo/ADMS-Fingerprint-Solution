<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'is_active',
        'secret_key',
        'headers',
        'retry_attempts',
        'timeout',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'headers' => 'array',
        'retry_attempts' => 'integer',
        'timeout' => 'integer',
    ];

    public function deliveryLogs()
    {
        return $this->hasMany(WebhookDeliveryLog::class);
    }
}
