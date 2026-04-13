<?php

namespace App\Tests\Unit;

use App\Service\PricingService;
use PHPUnit\Framework\TestCase;

final class PricingServiceTest extends TestCase
{
    public function testThrowsWhenMinimumPersonsRuleIsViolated(): void
    {
        $service = new PricingService();

        $this->expectException(\InvalidArgumentException::class);

        $service->calculate(18000, 3, 4, 0.0);
    }

    public function testAppliesReductionAtMinimumPlusFivePersons(): void
    {
        $service = new PricingService();

        $result = $service->calculate(22000, 11, 6, 8.5);

        self::assertSame(1002, $result['frais_livraison_centimes']);
        self::assertSame(2200, $result['reduction_appliquee_centimes']);
        self::assertSame(20802, $result['prix_total_centimes']);
    }

    public function testNoDeliveryFeesInBordeaux(): void
    {
        $service = new PricingService();

        $result = $service->calculate(18000, 4, 4, 0.0);

        self::assertSame(0, $result['frais_livraison_centimes']);
        self::assertSame(0, $result['reduction_appliquee_centimes']);
        self::assertSame(18000, $result['prix_total_centimes']);
    }
}
