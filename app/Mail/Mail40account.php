<?php

namespace App\Mail;

use App\Models\Note;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Mail40account extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Accept either a User instance or an email string for unregistered users
    public ?string $recipientEmail = null;

    public ?User $user = null;

    public ?Note $notes = null;

    public function __construct(User|string $userOrEmail, Note|int|null $notes)
    {
        if ($userOrEmail instanceof User) {
            $this->user = $userOrEmail;
            $this->recipientEmail = $userOrEmail->email;
        } else {
            // treat as email string
            $this->recipientEmail = trim((string) $userOrEmail);
            $this->user = null;
        }

        $this->notes = $notes instanceof Note ? $notes : Note::find((int) $notes);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromName = $this->user ? $this->user->name : $this->recipientEmail;
        $noteId = $this->notes ? $this->notes->id : '';

        return new Envelope(
            subject: 'Note no.'.$noteId.' has been shared with you by '.$fromName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.mail40account',
            with: [
                'user' => $this->user,
                'recipientEmail' => $this->recipientEmail,
                'notes' => $this->notes,
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
