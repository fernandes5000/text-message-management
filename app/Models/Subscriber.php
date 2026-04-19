<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'status',
        'source',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(SubscriberList::class, 'list_subscriber', 'subscriber_id', 'list_id');
    }
}
