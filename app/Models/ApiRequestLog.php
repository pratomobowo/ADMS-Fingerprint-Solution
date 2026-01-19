<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_token_id',
        'endpoint',
        'method',
        'query_params',
        'status_code',
        'response_time',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'query_params' => 'array',
        'response_time' => 'integer',
        'status_code' => 'integer',
    ];

    public function apiToken()
    {
        return $this->belongsTo(ApiToken::class);
    }
}
