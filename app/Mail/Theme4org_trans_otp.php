<?php

namespace App\Mail;

use App\Models\Theme4orgTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Theme4org_trans_otp extends Mailable
{
    use Queueable, SerializesModels;

    public Theme4orgTransaction $transaction;

    /**
     * Create a new message instance.
     */
    public function __construct(Theme4orgTransaction $transaction, public string $otp)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Theme4Org Trans Otp',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction_notification',
            with: [
                'transaction' => $this->transaction,
                'otp' => $this->otp,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
