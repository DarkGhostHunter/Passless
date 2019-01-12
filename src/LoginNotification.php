<?php

namespace DarkGhostHunter\Passless;

use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\UrlGenerator as Url;
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
     * The Config Repository
     *
     * @var Config
     */
    protected $config;

    /**
     * The URL Generator
     *
     * @var Url
     */
    protected $url;

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
     * @param Url $url
     */
    public function __construct(bool $remember,?string $intended, Config $config, Url $url)
    {
        $this->remember = $remember;
        $this->intended = $intended;
        $this->config = $config;
        $this->url = $url;
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
        $lifetime = $this->config->get('passless.lifetime');

        return (new MailMessage)
            ->greeting('Login to ' . $this->config->get('app.name'))
            ->line('Click the button to login. No password required.')
            ->action('Login', $this->createLoginUrl($notifiable, $lifetime))
            ->line("This link only last for $lifetime minutes.")
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
        return $this->path = $this->url->temporarySignedRoute(
            $this->config->get('passless.login.name'),
            now()->addMinutes($lifetime),
            array_filter([
                'id' => $notifiable->getAuthIdentifier(),
                'remember' => $this->remember,
                'intended' => $this->intended,
            ])
        );
    }
}