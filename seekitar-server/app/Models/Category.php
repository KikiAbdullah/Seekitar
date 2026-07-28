<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SerializesDatesAsUtc;

    protected $fillable = ['name', 'slug', 'parent_id', 'icon', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function customerRequests(): HasMany
    {
        return $this->hasMany(CustomerRequest::class);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
