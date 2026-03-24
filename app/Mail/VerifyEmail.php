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

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Email',
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
        $url = config('app.url') . '/api/v1/auth/verify-email/' . $this->user->email_verified_token;

        return $this->subject('Подтверждение регистрации — BetterLK')
            ->html("
                        <h2>Добро пожаловать в BetterLK!</h2>
                        <p>Для подтверждения регистрации нажмите на кнопку ниже:</p>
                        <a href='{$url}' style='background:#4F46E5;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;'>
                            Подтвердить email
                        </a>
                        <p style='color:#6B7280;margin-top:16px;'>Ссылка действительна 24 часа.</p>
                    ");
    }
}
