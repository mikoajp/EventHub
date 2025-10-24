<?php

namespace App\Domain\ValueObject;

final class EventDate
{
    private \DateTimeImmutable $value;

    private function __construct(\DateTimeImmutable $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $dateTime, ?\DateTimeZone $tz = null): self
    {
        $dt = new \DateTimeImmutable($dateTime, $tz);
        return new self($dt);
    }

    public static function fromNative(\DateTimeInterface $dateTime): self
    {
        return new self(\DateTimeImmutable::createFromInterface($dateTime));
    }

    public function toNative(): \DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format = DATE_ATOM): string
    {
        return $this->value->format($format);
    }

    public function isFuture(): bool
    {
        return $this->value > new \DateTimeImmutable();
    }

    public function equals(self $other): bool
    {
        return $this->value == $other->value; // DateTimeImmutable equality by timestamp/timezone
    }
}
