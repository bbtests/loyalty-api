<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $code
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('constants.app.name');

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->markdown('emails.reset-password', [
                'notifiable' => $notifiable,
                'code' => $this->code,
                'appName' => $appName,
            ]);

    }
}
