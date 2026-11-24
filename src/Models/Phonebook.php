<?php

namespace Arrocy\Phonebook\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'enabled', 'name', 'avatar', 'data'])]
class Phonebook extends Model
{
    protected $table = 'ez_phonebooks';

    protected $casts = [
        'data' => 'array',
    ];

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $pattern = '%'.$search.'%';

        return $query->where(fn (Builder $query): Builder => $query
            ->where('name', 'like', $pattern)
            ->orWhere('data', 'like', $pattern));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}