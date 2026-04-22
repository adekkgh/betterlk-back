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

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $token,
    )
    {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password',
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

    public function build(): self
    {
        // Ссылка ведёт на фронтенд, не на API
        // Фронт получит токен из URL и отправит его на API
        $url = env('FRONTEND_URL') . '/reset-password?token=' . $this->token;

        return $this->subject('Сброс пароля — BetterLK')
            ->html("
                        <h2>Сброс пароля</h2>
                        <p>Вы запросили сброс пароля для аккаунта <strong>{$this->user->email}</strong>.</p>
                        <p>Нажмите на кнопку ниже чтобы задать новый пароль:</p>
                        <a href='{$url}' style='background:#7C5CFC;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;margin:16px 0;'>
                            Сбросить пароль
                        </a>
                        <p style='color:#706F6C;font-size:13px;'>Ссылка действительна 60 минут. Если вы не запрашивали сброс — просто проигнорируйте это письмо.</p>
                    ");
    }
}
