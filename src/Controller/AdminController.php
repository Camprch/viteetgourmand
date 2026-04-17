<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminEmployeeCreateType;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.contact_sender%')]
        private readonly string $sender,
    ) {
    }

    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'employees' => $userRepository->findEmployees(),
        ]);
    }

    #[Route('/employees/new', name: 'app_admin_employee_new', methods: ['GET', 'POST'])]
    public function createEmployee(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(AdminEmployeeCreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employee = (new User())
                ->setEmail((string) $form->get('email')->getData())
                ->setRoles(['ROLE_EMPLOYEE'])
                ->setNom('Employe')
                ->setPrenom('Nouveau')
                ->setTelephone(null)
                ->setAdresse(null)
                ->setActif(true)
                ->setCreatedAt(new \DateTimeImmutable());

            $employee->setPassword(
                $passwordHasher->hashPassword($employee, (string) $form->get('plainPassword')->getData())
            );

            try {
                $entityManager->persist($employee);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Cet email existe deja.');

                return $this->render('admin/employee_new.html.twig', [
                    'employeeForm' => $form,
                ]);
            }

            try {
                $mailer->send(
                    (new TemplatedEmail())
                        ->from($this->sender)
                        ->to((string) $employee->getEmail())
                        ->subject('Creation de votre compte employe')
                        ->htmlTemplate('emails/employee_account_created.html.twig')
                        ->context([
                            'employee' => $employee,
                        ])
                );
            } catch (TransportExceptionInterface) {
                // Keep employee creation successful even if mail transport is unavailable.
            }

            $this->addFlash('success', 'Compte employe cree (ROLE_EMPLOYEE).');

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/employee_new.html.twig', [
            'employeeForm' => $form,
        ]);
    }

    #[Route('/employees/{id}/toggle-active', name: 'app_admin_employee_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleEmployeeActive(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('toggle_employee_' . $user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_dashboard');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_EMPLOYEE', $roles, true) || in_array('ROLE_ADMIN', $roles, true)) {
            $this->addFlash('error', 'Operation autorisee uniquement sur un compte employe.');

            return $this->redirectToRoute('app_admin_dashboard');
        }

        $user->setActif(!((bool) $user->isActif()));
        $entityManager->flush();

        $this->addFlash('success', 'Statut employe mis a jour.');

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
