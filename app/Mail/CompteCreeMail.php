<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompteCreeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $prenom,
        public string $email,
        public string $motDePasse,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre compte Klymap a été créé');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.compte-cree');
    }
}
