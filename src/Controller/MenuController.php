<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/menus')]
final class MenuController extends AbstractController
{
    #[Route('', name: 'app_menu_index', methods: ['GET'])]
    public function index(Request $request, MenuRepository $menuRepository): Response
    {
        $filters = $this->extractFilters($request);

        return $this->render('menu/index.html.twig', [
            'menus' => $menuRepository->findActiveFiltered($filters),
            'filters' => [
                'theme' => $filters['theme'] ?? '',
                'regime' => $filters['regime'] ?? '',
                'personnes_min' => $filters['personnes_min'],
                'prix_min' => $filters['prix_min'],
                'prix_max' => $filters['prix_max'],
            ],
            'themes' => $menuRepository->findActiveThemes(),
            'regimes' => $menuRepository->findActiveRegimes(),
        ]);
    }

    #[Route('/{id}', name: 'app_menu_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Menu $menu): Response
    {
        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }

    /**
     * @return array{
     *     theme: string,
     *     regime: string,
     *     personnes_min: int|null,
     *     prix_min: string,
     *     prix_max: string,
     *     prix_min_centimes: int|null,
     *     prix_max_centimes: int|null
     * }
     */
    private function extractFilters(Request $request): array
    {
        $theme = trim($request->query->getString('theme', ''));
        $regime = trim($request->query->getString('regime', ''));
        $personnesMinRaw = trim($request->query->getString('personnes_min', ''));
        $prixMin = trim($request->query->getString('prix_min', ''));
        $prixMax = trim($request->query->getString('prix_max', ''));

        return [
            'theme' => $theme,
            'regime' => $regime,
            'personnes_min' => $personnesMinRaw !== '' ? max(0, (int) $personnesMinRaw) : null,
            'prix_min' => $prixMin,
            'prix_max' => $prixMax,
            'prix_min_centimes' => $this->parseEuroToCentimes($prixMin),
            'prix_max_centimes' => $this->parseEuroToCentimes($prixMax),
        ];
    }

    private function parseEuroToCentimes(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);
        if (!is_numeric($normalized)) {
            return null;
        }

        return max(0, (int) round(((float) $normalized) * 100));
    }
}
