<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'number',
        'status',
        'aliases',
        'workflow',
        'uses_count',
        'opt_ins_count',
    ];

    protected $casts = [
        'aliases'  => 'array',
        'workflow' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(SubscriberList::class, 'keyword_lists', 'keyword_id', 'list_id');
    }
}
