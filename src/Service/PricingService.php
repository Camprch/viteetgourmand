<?php

namespace App\Service;

final class PricingService
{
    public function calculate(
        int $prixMenuTotalCentimes,
        int $nbPersonnes,
        int $personnesMin,
        float $distanceKm
    ): array {
        $this->assertMinimumPersons($nbPersonnes, $personnesMin);

        $fraisLivraisonCentimes = $distanceKm > 0
            ? 500 + (int) round($distanceKm * 59)
            : 0;

        $reductionAppliqueeCentimes = $nbPersonnes >= ($personnesMin + 5)
            ? (int) round($prixMenuTotalCentimes * 0.10)
            : 0;

        $prixTotalCentimes = $prixMenuTotalCentimes + $fraisLivraisonCentimes - $reductionAppliqueeCentimes;

        return [
            'prix_menu_total_centimes' => $prixMenuTotalCentimes,
            'frais_livraison_centimes' => $fraisLivraisonCentimes,
            'reduction_appliquee_centimes' => $reductionAppliqueeCentimes,
            'prix_total_centimes' => $prixTotalCentimes,
        ];
    }

    public function assertMinimumPersons(int $nbPersonnes, int $personnesMin): void
    {
        if ($nbPersonnes < $personnesMin) {
            throw new \InvalidArgumentException('Le nombre de personnes est inferieur au minimum requis.');
        }
    }
}
