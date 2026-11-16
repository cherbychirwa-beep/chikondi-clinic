<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeSetting extends Model
{
    protected $fillable = ['amount', 'effective_from', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_from' => 'date'];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
