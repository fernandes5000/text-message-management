<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Message $smsMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[TMM Demo] SMS Sent: {$this->smsMessage->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.message-sent',
        );
    }
}
