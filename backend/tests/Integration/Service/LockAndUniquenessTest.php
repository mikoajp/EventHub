<?php

namespace App\Tests\Integration\Service;

use App\Entity\IdempotencyKey;
use App\Repository\IdempotencyKeyRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LockAndUniquenessTest extends KernelTestCase
{
    public function testCompositeUniqueConfigured(): void
    {
        self::bootKernel();
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $class = $em->getClassMetadata(IdempotencyKey::class);
        $found = false;
        foreach ($class->table['uniqueConstraints'] ?? [] as $uc) {
            if (in_array('idempotency_key', $uc['columns'] ?? []) && in_array('command_class', $uc['columns'] ?? [])) {
                $found = true; break;
            }
        }
        $this->assertTrue($found, 'Composite unique (idempotency_key, command_class) must exist');
    }
}
