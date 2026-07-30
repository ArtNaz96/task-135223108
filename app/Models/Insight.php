<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    use HasFactory;

    protected $table = 'insights';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}