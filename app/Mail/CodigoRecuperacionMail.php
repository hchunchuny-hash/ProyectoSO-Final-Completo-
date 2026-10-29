<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CodigoRecuperacionMail extends Mailable
{
    public function __construct(
        public User $usuario,
        public string $codigo,
        public int $minutos,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código de recuperación de SysMonitor');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.codigo');
    }
}
