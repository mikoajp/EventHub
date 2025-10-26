<?php

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testCreateValidEmail(): void
    {
        $email = Email::fromString('test@example.com');
        
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame('test@example.com', $email->toString());
        $this->assertSame('test@example.com', (string) $email);
    }

    public function testEmailIsNormalizedToLowercase(): void
    {
        $email = Email::fromString('Test@EXAMPLE.COM');
        
        $this->assertSame('test@example.com', $email->toString());
    }

    public function testEmailIsTrimmed(): void
    {
        $email = Email::fromString('  test@example.com  ');
        
        $this->assertSame('test@example.com', $email->toString());
    }

    public function testEmailEquality(): void
    {
        $email1 = Email::fromString('test@example.com');
        $email2 = Email::fromString('test@example.com');
        $email3 = Email::fromString('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function testEmailEqualityIsCaseInsensitive(): void
    {
        $email1 = Email::fromString('Test@Example.com');
        $email2 = Email::fromString('test@example.com');
        
        $this->assertTrue($email1->equals($email2));
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function testInvalidEmailThrowsException(string $invalidEmail): void
    {
        $this->expectException(\App\Exception\Validation\InvalidEmailException::class);
        
        Email::fromString($invalidEmail);
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'empty string' => [''],
            'whitespace only' => ['   '],
            'no @ symbol' => ['testexample.com'],
            'no domain' => ['test@'],
            'no local part' => ['@example.com'],
            'multiple @ symbols' => ['test@@example.com'],
            'invalid characters' => ['test test@example.com'],
            'no TLD' => ['test@example'],
        ];
    }
}
