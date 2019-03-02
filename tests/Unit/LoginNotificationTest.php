<?php

namespace Tests\Unit;

use DarkGhostHunter\Passless\LoginNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\TestCase;
use Tests\RegistersPackage;

class LoginNotificationTest extends TestCase
{
    use RegistersPackage;

    /** @var LoginNotification */
    protected $notification;

    protected function setUp() : void
    {
        parent::setUp();

        $this->notification = $this->app->make(
            LoginNotification::class, [
                'remember' => true,
                'intended' => 'http://app.com/intended'
            ]
        );

    }

    public function testNotificationArrayEmpty()
    {
        $array = $this->notification->toArray(
            new \Illuminate\Foundation\Auth\User
        );

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    public function testOnlyViaMail()
    {
        $array = $this->notification->via(new User);

        $this->assertEquals(['mail'], $array);
    }

    public function testCreatesMail()
    {
        $mail = $this->notification->toMail(
            User::unguarded(function () {
                return new User(['id' => 1]);
            })
        );

        /** @var \Illuminate\Mail\Markdown $markdown */
        $markdown = $this->app->make(\Illuminate\Mail\Markdown::class);

        $render = $markdown->render($mail->markdown, $mail->data());

        $this->assertStringContainsString(urlencode('http://app.com/intended'), $render);
        $this->assertStringContainsString('http://localhost/passless/login', $render);
        $this->assertStringContainsString('expires=', $render);
        $this->assertStringContainsString('intended=http%3A%2F%2Fapp.com%2Fintended', $render);
        $this->assertStringContainsString('id=1', $render);
        $this->assertStringContainsString('remember=1', $render);
        $this->assertStringContainsString('signature', $render);
    }

    public function testMailShouldQueue()
    {
        $this->assertInstanceOf(ShouldQueue::class, $this->notification);
        $this->assertArrayHasKey('Illuminate\Bus\Queueable', class_uses($this->notification));
    }
}