<?php

namespace App\Tests\Unit;

use App\Service\OrderWorkflowService;
use PHPUnit\Framework\TestCase;

final class OrderWorkflowServiceTest extends TestCase
{
    public function testAllowsValidTransition(): void
    {
        $service = new OrderWorkflowService();

        self::assertTrue($service->canTransition('accepte', 'en_preparation'));
        self::assertTrue($service->canTransition('livre', 'terminee'));
    }

    public function testRejectsInvalidTransition(): void
    {
        $service = new OrderWorkflowService();

        self::assertFalse($service->canTransition('accepte', 'livre'));
    }

    public function testThrowsOnInvalidTransitionAssertion(): void
    {
        $service = new OrderWorkflowService();

        $this->expectException(\DomainException::class);

        $service->assertTransition('en_preparation', 'terminee');
    }
}
