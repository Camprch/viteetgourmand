<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(AvisRepository $avisRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'avis' => $avisRepository->findBy(['valide' => true], ['createdAt' => 'DESC'], 6),
        ]);
    }
}
