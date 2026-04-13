<?php

namespace App\Twig;

use App\Repository\HoraireRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FooterExtension extends AbstractExtension
{
    public function __construct(private readonly HoraireRepository $horaireRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vg_footer_horaires', $this->getFooterHoraires(...)),
        ];
    }

    /**
     * @return array<int, array{label: string, ferme: bool, ouverture: string|null, fermeture: string|null}>
     */
    public function getFooterHoraires(): array
    {
        $labels = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        $items = [];
        foreach ($this->horaireRepository->findOrderedByJour() as $horaire) {
            $jour = (int) $horaire->getJour();
            $items[$jour] = [
                'label' => $labels[$jour] ?? ('Jour '.$jour),
                'ferme' => (bool) $horaire->isFerme(),
                'ouverture' => $horaire->getHeureOuverture()?->format('H:i'),
                'fermeture' => $horaire->getHeureFermeture()?->format('H:i'),
            ];
        }

        $result = [];
        for ($jour = 1; $jour <= 7; ++$jour) {
            $result[] = $items[$jour] ?? [
                'label' => $labels[$jour],
                'ferme' => true,
                'ouverture' => null,
                'fermeture' => null,
            ];
        }

        return $result;
    }
}
