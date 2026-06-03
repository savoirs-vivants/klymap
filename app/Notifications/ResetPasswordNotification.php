<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends BaseResetPassword
{
    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — Klymap')
            ->view('mail.reset-password', [
                'url'   => $url,
                'count' => Config::get('auth.passwords.'.Config::get('auth.defaults.passwords').'.expire'),
            ]);
    }
}
