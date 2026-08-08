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

class UserEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private $user, private $notes)
    {
        // Accept either model instances or ids and resolve to models here so job serialization stays small
        if ($user instanceof User) {
            $this->user = $user;
        } else {
            $this->user = User::find($user);
        }

        if ($notes instanceof Note) {
            $this->notes = $notes;
        } else {
            $this->notes = Note::find($notes);
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Note no.'.$this->notes->id.' has been shared with you by'.$this->user->username,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.userEmail',
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
