<?php

namespace DarkGhostHunter\Passless;

use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * If the user should be remembered
     *
     * @var bool
     */
    protected $remember;

    /**
     * The app name
     *
     * @var string
     */
    protected $app_name;

    /**
     * The link lifetime in minutes
     *
     * @var integer
     */
    protected $lifetime;

    /**
     * The Passless login route name
     *
     * @var string
     */
    protected $passless_route;

    /**
     * URL Path of authentication
     *
     * @var string
     */
    protected $path;

    /**
     * The path to redirect the user after login
     *
     * @var string
     */
    protected $intended;

    /**
     * LoginAttemptNotification constructor.
     *
     * @param bool $remember
     * @param string|null $intended
     * @param Config $config
     */
    public function __construct(bool $remember,?string $intended, Config $config)
    {
        $this->remember = $remember;
        $this->intended = $intended;
        $this->app_name = $config->get('app.name');
        $this->lifetime = $config->get('passless.lifetime');
        $this->passless_route = $config->get('passless.login.name');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(Authenticatable $notifiable)
    {
        return (new MailMessage)
            ->greeting("Login to {$this->app_name}")
            ->line('Click the button to login. No password required.')
            ->action('Login', $this->createLoginUrl($notifiable, $this->lifetime))
            ->line("This link only lasts for {$this->lifetime} minutes.")
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * Creates a signed temporary URL to the Login action
     *
     * @param Authenticatable $notifiable
     * @param int $lifetime
     * @return string
     */
    public function createLoginUrl($notifiable, int $lifetime)
    {
        return $this->path = app('url')->temporarySignedRoute(
            $this->passless_route,
            now()->addMinutes($lifetime),
            array_filter([
                'id' => $notifiable->getAuthIdentifier(),
                'remember' => $this->remember,
                'intended' => $this->intended,
            ])
        );
    }
}
