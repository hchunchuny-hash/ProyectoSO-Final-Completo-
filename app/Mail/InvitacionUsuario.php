<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

class InvitacionUsuario extends Mailable
{
    public function __construct(
        public User $usuario,
        public string $enlace,
        public Carbon $expira,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitación a SysMonitor');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitacion');
    }
}
