<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Message;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Message $smsMessage,
        public readonly Subscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        $name = trim("{$this->subscriber->first_name} {$this->subscriber->last_name}");
        return new Envelope(
            subject: "[TMM Demo] SMS to {$name}: {$this->smsMessage->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.message-sent',
        );
    }
}
