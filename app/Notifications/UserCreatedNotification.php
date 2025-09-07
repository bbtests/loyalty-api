<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        $frontendUrl = config('constants.app.frontend_url');
        $loginUrl = config('constants.urls.login');
        $supportEmail = config('constants.notifications.support_email');
        $fullLoginUrl = \rtrim($frontendUrl, '/').'/'.\ltrim($loginUrl, '/');

        return (new MailMessage)
            ->subject('Your Account Has Been Created')
            ->greeting('Hello '.$notifiable->first_name.'!')
            ->line("Congratulations! Your account has been successfully created. Welcome to {$appName}.")
            ->action('Login Now', $fullLoginUrl)
            ->line("If you have any questions or need assistance, please contact us at {$supportEmail}")
            ->salutation('Welcome to the team!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account Created',
            'message' => 'Your account has been created. Please check your email for login credentials.',
            'type' => 'account_created',
            'action_url' => \rtrim(config('constants.app.frontend_url'), '/').'/'.\ltrim(config('constants.urls.login'), '/'),
            'action_text' => 'Login Now',
            'requires_password_change' => true,
        ];
    }
}
