<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso interno: el cliente aceptó o rechazó la cotización desde el link. */
class QuoteClientResponded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        $verb = $this->quote->client_response === 'aceptada' ? 'aceptó' : 'rechazó';

        return new Envelope(
            subject: "{$this->quote->opportunity->ranch->client->name} {$verb} la cotización {$this->quote->number}",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.quote-client-response');
    }
}
