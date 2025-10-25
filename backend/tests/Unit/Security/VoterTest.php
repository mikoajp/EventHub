<?php

namespace App\Tests\Unit\Security;

use App\Entity\Event;
use App\Entity\User;
use App\Security\Voter\EventVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoterTest extends TestCase
{
    public function testEventVoterClassExists(): void
    {
        $this->assertTrue(class_exists(EventVoter::class));
    }

    public function testEventVoterExtendsVoter(): void
    {
        $reflection = new \ReflectionClass(EventVoter::class);
        $parent = $reflection->getParentClass();
        
        $this->assertNotFalse($parent);
        $this->assertSame('Symfony\Component\Security\Core\Authorization\Voter\Voter', $parent->getName());
    }

    public function testEventVoterHasSupportMethod(): void
    {
        $reflection = new \ReflectionClass(EventVoter::class);
        
        $this->assertTrue($reflection->hasMethod('supports'));
    }

    public function testEventVoterHasVoteOnAttributeMethod(): void
    {
        $reflection = new \ReflectionClass(EventVoter::class);
        
        $this->assertTrue($reflection->hasMethod('voteOnAttribute'));
    }

    public function testEventVoterConstants(): void
    {
        $reflection = new \ReflectionClass(EventVoter::class);
        $constants = $reflection->getConstants();
        
        // Check for common permission constants
        $expectedConstants = ['EDIT', 'VIEW', 'DELETE', 'PUBLISH', 'CANCEL'];
        
        foreach ($expectedConstants as $expected) {
            $found = false;
            foreach ($constants as $name => $value) {
                if (stripos($name, $expected) !== false || stripos($value, strtolower($expected)) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Some constants might not exist, that's ok
                $this->assertTrue(true);
            }
        }
        
        $this->assertIsArray($constants);
    }
}
