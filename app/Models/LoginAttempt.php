<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['username', 'ip_address', 'was_successful', 'attempted_at'];

    protected function casts(): array
    {
        return ['was_successful' => 'boolean', 'attempted_at' => 'datetime'];
    }
}
