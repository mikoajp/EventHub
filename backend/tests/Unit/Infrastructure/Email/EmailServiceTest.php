<?php

namespace App\Tests\Unit\Infrastructure\Email;

use App\Infrastructure\Email\EmailServiceInterface;
use App\Infrastructure\Email\SymfonyMailerAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

final class EmailServiceTest extends TestCase
{
    public function testEmailServiceInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(EmailServiceInterface::class));
    }

    public function testSymfonyMailerAdapterExists(): void
    {
        $this->assertTrue(class_exists(SymfonyMailerAdapter::class));
    }

    public function testSymfonyMailerAdapterImplementsInterface(): void
    {
        $reflection = new \ReflectionClass(SymfonyMailerAdapter::class);
        
        $this->assertTrue(
            $reflection->implementsInterface(EmailServiceInterface::class),
            'SymfonyMailerAdapter should implement EmailServiceInterface'
        );
    }

    public function testEmailServiceInterfaceHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(EmailServiceInterface::class);
        
        $this->assertTrue($reflection->hasMethod('sendEmail'));
        $this->assertTrue($reflection->hasMethod('sendTicketConfirmation'));
        $this->assertTrue($reflection->hasMethod('sendEventPublishedNotification'));
        $this->assertTrue($reflection->hasMethod('sendEventCancelledNotification'));
    }

    public function testSymfonyMailerAdapterCanBeInstantiated(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $twig = $this->createMock(\Twig\Environment::class);
        $adapter = new SymfonyMailerAdapter($mailer, $twig, 'test@example.com');
        
        $this->assertInstanceOf(SymfonyMailerAdapter::class, $adapter);
        $this->assertInstanceOf(EmailServiceInterface::class, $adapter);
    }
}
