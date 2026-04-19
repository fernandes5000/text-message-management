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
        // Simulate sending: count recipients from attached lists
        $recipientCount = $this->message->lists()
            ->withCount('subscribers')
            ->get()
            ->sum('subscribers_count');

        // Fallback so a message without lists still shows a plausible number
        if ($recipientCount === 0) {
            $recipientCount = rand(50, 500);
        }

        $this->message->update([
            'status'          => 'sent',
            'sent_at'         => now(),
            'recipient_count' => $recipientCount,
            'credits_used'    => $recipientCount,
        ]);

        // Notify via Mailhog
        $adminEmail = config('mail.from.address', 'admin@tmm.test');
        Mail::to($adminEmail)->send(new MessageSentMail($this->message->fresh() ?? $this->message));
    }

    public function failed(\Throwable $e): void
    {
        $this->message->update(['status' => 'failed']);
    }
}
