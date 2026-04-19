<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by',
        'name',
        'body',
        'status',
        'send_type',
        'scheduled_at',
        'recurrence',
        'from_number',
        'use_header',
        'header',
        'media_url',
        'recipient_count',
        'credits_used',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'use_header'   => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(SubscriberList::class, 'message_lists', 'message_id', 'list_id');
    }
}
