<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCode extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, public string $code)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Two Factor Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
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

    // TODO: change verification letter
    public function build(): self
    {
        return $this->subject('Код входа — BetterLK')
            ->html("
                        <h2>Код подтверждения входа</h2>
                        <p>Ваш код для входа в BetterLK:</p>
                        <div style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#4F46E5;margin:24px 0;'>
                            {$this->code}
                        </div>
                        <p style='color:#6B7280;'>Код действителен 10 минут. Никому не сообщайте его.</p>
                    ");
    }
}
