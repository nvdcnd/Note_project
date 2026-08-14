<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mời một email CHƯA có tài khoản vào tổ chức, kèm link đăng ký.
 */
class OrganizationInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $recipientEmail,
        public string $inviteToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bạn được mời vào tổ chức "'.$this->organization->name.'" trên Noteket',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization_invitation',
            with: [
                'organization' => $this->organization,
                'recipientEmail' => $this->recipientEmail,
                'inviteUrl' => route('invitation.show', $this->inviteToken),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
