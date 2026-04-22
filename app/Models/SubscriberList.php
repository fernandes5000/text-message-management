<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SubscriberListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriberList extends Model
{
    /** @use HasFactory<SubscriberListFactory> */
    use HasFactory;

    protected $table = 'lists';

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'sync_source',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'list_subscriber', 'list_id', 'subscriber_id');
    }
}
