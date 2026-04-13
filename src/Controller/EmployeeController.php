<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmployeeController extends AbstractController
{
    #[Route('/employee', name: 'app_employee_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('employee/index.html.twig');
    }
}
