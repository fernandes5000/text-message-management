<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\MessageSentMail;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Message $message) {}

    public function handle(): void
    {
        // Collect unique opted-in subscribers across all attached lists, scoped to the org
        $orgId = $this->message->organization_id;

        $subscribers = \App\Models\Subscriber::query()
            ->where('organization_id', $orgId)
            ->where('status', 'opted_in')
            ->whereHas('lists', fn ($q) => $q->whereIn(
                'lists.id',
                $this->message->lists()->pluck('lists.id')
            ))
            ->get();

        $recipientCount = $subscribers->count();

        $this->message->update([
            'status'          => 'sent',
            'sent_at'         => now(),
            'recipient_count' => $recipientCount,
            'credits_used'    => $recipientCount,
        ]);

        $message = $this->message->fresh() ?? $this->message;

        // Send one simulated SMS email per subscriber
        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email ?? config('mail.from.address'))
                ->send(new MessageSentMail($message, $subscriber));
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->message->update(['status' => 'failed']);
    }
}
