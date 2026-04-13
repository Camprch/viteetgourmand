<?php

namespace App\Service;

final class OrderWorkflowService
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'accepte' => ['en_preparation'],
        'en_preparation' => ['en_cours_livraison'],
        'en_cours_livraison' => ['livre'],
        'livre' => ['attente_retour_materiel', 'terminee'],
        'attente_retour_materiel' => ['terminee'],
        'terminee' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return \in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true);
    }

    public function assertTransition(string $from, string $to): void
    {
        if (!$this->canTransition($from, $to)) {
            throw new \DomainException(sprintf('Transition invalide: %s -> %s', $from, $to));
        }
    }

    /**
     * @return list<string>
     */
    public function nextStatuses(string $from): array
    {
        return self::ALLOWED_TRANSITIONS[$from] ?? [];
    }
}
