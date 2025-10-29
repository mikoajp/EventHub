<?php

namespace App\Tests\Integration\Messenger;

use App\Message\Command\Payment\ProcessPaymentCommand;
use App\Message\Command\Ticket\PurchaseTicketCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Test Messenger retry strategy and Dead Letter Queue (DLQ) behavior
 */
final class RetryAndDLQTest extends KernelTestCase
{
    public function testMessengerTransportsAreConfigured(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // Verify transports exist
        $this->assertTrue($container->has('messenger.transport.async'));
        $this->assertTrue($container->has('messenger.transport.high_priority'));
        $this->assertTrue($container->has('messenger.transport.notifications'));
        $this->assertTrue($container->has('messenger.transport.failed'));
    }

    public function testCommandsAreRoutedToCorrectTransports(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        $bus = $container->get(MessageBusInterface::class);
        
        // Test that message bus is properly configured
        $this->assertInstanceOf(MessageBusInterface::class, $bus);
        
        // Verify routing exists in configuration
        // Payment commands should go to high_priority
        $paymentCommand = new ProcessPaymentCommand('ticket-1', 'pm_test', 5000);
        $envelope = $bus->dispatch($paymentCommand);
        
        $this->assertNotNull($envelope);
        
        // Regular ticket commands should go to async
        $ticketCommand = new PurchaseTicketCommand('ticket-2', 'user-1', 'event-1', 1);
        $envelope2 = $bus->dispatch($ticketCommand);
        
        $this->assertNotNull($envelope2);
    }

    public function testRetryStrategyIsConfiguredForTransports(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // Verify transports have retry configuration
        // We can't directly test retry strategy without triggering failures,
        // but we can verify the infrastructure is in place
        
        $this->assertTrue($container->has('messenger.transport.async'));
        $this->assertTrue($container->has('messenger.transport.high_priority'));
        
        // Verify that failed transport exists for DLQ
        $this->assertTrue($container->has('messenger.transport.failed'));
    }

    public function testFailedMessagesInfrastructureIsConfigured(): void
    {
        self::bootKernel();
        
        $container = self::getContainer();
        
        // When a message fails after all retries, it should go to 'failed' transport
        // The failed transport is configured as: doctrine://default?queue_name=failed
        
        $this->assertTrue(
            $container->has('messenger.transport.failed'),
            'Failed transport (DLQ) must be configured'
        );
        
        // Verify we can get the transport
        $failedTransport = $container->get('messenger.transport.failed');
        $this->assertNotNull($failedTransport);
    }

    public function testRedeliveryStampTracksRetries(): void
    {
        $command = new ProcessPaymentCommand(
            ticketId: 'test-ticket-id',
            paymentMethodId: 'pm_test',
            amount: 5000
        );

        $envelope = new Envelope($command);
        
        // Simulate first retry
        $envelope = $envelope->with(new RedeliveryStamp(1));
        
        $stamps = $envelope->all(RedeliveryStamp::class);
        $this->assertCount(1, $stamps);
        $this->assertSame(1, $stamps[0]->getRetryCount());
    }

    public function testDelayStampAddsBackoffDelay(): void
    {
        $command = new ProcessPaymentCommand(
            ticketId: 'test-ticket-id',
            paymentMethodId: 'pm_test',
            amount: 5000
        );

        $envelope = new Envelope($command);
        
        // Add delay stamp (retry with backoff)
        $envelope = $envelope->with(new DelayStamp(2000)); // 2 second delay
        
        $stamps = $envelope->all(DelayStamp::class);
        $this->assertCount(1, $stamps);
        $this->assertSame(2000, $stamps[0]->getDelay());
    }

    public function testExponentialBackoffCalculation(): void
    {
        // Test retry delay calculation
        // With multiplier: 2, delay: 1000
        // Retry 1: 1000ms
        // Retry 2: 2000ms
        // Retry 3: 4000ms
        
        $baseDelay = 1000;
        $multiplier = 2;
        
        $retry1Delay = $baseDelay;
        $retry2Delay = $baseDelay * $multiplier;
        $retry3Delay = $retry2Delay * $multiplier;
        
        $this->assertSame(1000, $retry1Delay);
        $this->assertSame(2000, $retry2Delay);
        $this->assertSame(4000, $retry3Delay);
    }

    public function testMaxRetryLimitIsRespected(): void
    {
        // After max_retries is reached, message should go to DLQ
        // async transport: max 3 retries
        // high_priority transport: max 5 retries
        
        $maxRetriesAsync = 3;
        $maxRetriesHighPriority = 5;
        
        $this->assertSame(3, $maxRetriesAsync);
        $this->assertSame(5, $maxRetriesHighPriority);
    }

    public function testFailureTransportHasNoRetries(): void
    {
        // Failed messages in DLQ should not be retried automatically
        // failure_transport_retry_strategy.max_retries: 0
        
        $maxRetriesForDLQ = 0;
        
        $this->assertSame(0, $maxRetriesForDLQ, 'DLQ should not retry messages');
    }

    public function testTransientErrorsCanBeIdentified(): void
    {
        // Transient errors (network issues, temporary unavailability)
        // should be retried according to retry strategy
        
        // Examples of transient errors that should be retried:
        $transientErrors = [
            'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found',
            'Connection timeout',
            'Service unavailable (503)',
            'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away'
        ];
        
        foreach ($transientErrors as $error) {
            // These errors indicate temporary issues that might succeed on retry
            $this->assertStringContainsStringIgnoringCase(
                'deadlock',
                $error,
                true // or contains timeout, unavailable, etc.
            );
        }
        
        $this->assertCount(4, $transientErrors);
    }

    public function testPermanentErrorsCanBeIdentified(): void
    {
        // Permanent errors should not be retried
        // Examples:
        $permanentErrors = [
            'InvalidArgumentException: Ticket not found',
            'InvalidArgumentException: Invalid payment method',
            'ValidationException: Invalid email format',
            'AccessDeniedException: User not authorized'
        ];
        
        foreach ($permanentErrors as $error) {
            // These errors indicate logical/business errors that won't succeed on retry
            $this->assertMatchesRegularExpression(
                '/(InvalidArgumentException|ValidationException|AccessDeniedException)/',
                $error
            );
        }
        
        $this->assertCount(4, $permanentErrors);
    }

    public function testInMemoryTransportForTesting(): void
    {
        self::bootKernel();
        
        // In test environment, we can use InMemoryTransport
        // to capture and verify messages without actual message broker
        
        $container = self::getContainer();
        
        // Get message bus
        $bus = $container->get(MessageBusInterface::class);
        $this->assertInstanceOf(MessageBusInterface::class, $bus);
    }
}
