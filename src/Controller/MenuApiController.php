<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MenuApiController extends AbstractController
{
    #[Route('/api/menus', name: 'app_api_menus', methods: ['GET'])]
    public function list(Request $request, MenuRepository $menuRepository): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $menus = $menuRepository->findActiveFiltered($filters);

        $payload = array_map(function (Menu $menu): array {
            $primaryImage = $this->findPrimaryImage($menu);

            return [
                'id' => $menu->getId(),
                'titre' => $menu->getTitre(),
                'description' => $menu->getDescription(),
                'theme' => $menu->getTheme(),
                'regime' => $menu->getRegime(),
                'prix_min_centimes' => $menu->getPrixMinCentimes(),
                'personnes_min' => $menu->getPersonnesMin(),
                'stock' => $menu->getStock(),
                'image_principale_url' => $primaryImage?->getUrl(),
                'image_principale_alt' => $primaryImage?->getAltText(),
            ];
        }, $menus);

        return $this->json([
            'count' => count($payload),
            'menus' => $payload,
        ]);
    }

    /**
     * @return array{theme: string, regime: string, personnes_min: int|null, prix_min_centimes: int|null, prix_max_centimes: int|null}
     */
    private function extractFilters(Request $request): array
    {
        $theme = trim($request->query->getString('theme', ''));
        $regime = trim($request->query->getString('regime', ''));
        $personnesMinRaw = trim($request->query->getString('personnes_min', ''));
        $prixMin = trim($request->query->getString('prix_min', ''));
        $prixMax = trim($request->query->getString('prix_max', ''));
        $prixMinCentimes = $this->parseEuroToCentimes($prixMin);
        $prixMaxCentimes = $this->parseEuroToCentimes($prixMax);

        if ($prixMinCentimes !== null && $prixMaxCentimes !== null && $prixMinCentimes > $prixMaxCentimes) {
            [$prixMinCentimes, $prixMaxCentimes] = [$prixMaxCentimes, $prixMinCentimes];
        }

        return [
            'theme' => $theme,
            'regime' => $regime,
            'personnes_min' => $personnesMinRaw !== '' ? max(0, (int) $personnesMinRaw) : null,
            'prix_min_centimes' => $prixMinCentimes,
            'prix_max_centimes' => $prixMaxCentimes,
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

    private function findPrimaryImage(Menu $menu): ?\App\Entity\MenuImage
    {
        $images = $menu->getMenuImages()->toArray();
        if ($images === []) {
            return null;
        }

        usort($images, static function (\App\Entity\MenuImage $a, \App\Entity\MenuImage $b): int {
            $primarySort = ((int) ($b->isPrincipale() ?? false)) <=> ((int) ($a->isPrincipale() ?? false));
            if ($primarySort !== 0) {
                return $primarySort;
            }

            return ($a->getOrdreAffichage() ?? 0) <=> ($b->getOrdreAffichage() ?? 0);
        });

        return $images[0] ?? null;
    }
}
