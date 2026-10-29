<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContrasenaCambiadaMail extends Mailable
{
    public function __construct(
        public User $usuario,
        public string $cuando,
        public ?string $ip = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu contraseña de SysMonitor fue cambiada');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.clave_cambiada');
    }
}

