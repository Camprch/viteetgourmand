<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Form\PasswordResetRequestType;
use App\Form\PasswordResetType;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reset-password')]
final class PasswordResetController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.contact_sender%')]
        private readonly string $sender,
    ) {
    }

    #[Route('', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        $form = $this->createForm(PasswordResetRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));

            $user = $userRepository->findOneBy(['email' => $email, 'actif' => true]);
            if ($user instanceof User) {
                $tokenValue = bin2hex(random_bytes(32));
                $now = new \DateTimeImmutable();
                $tokenRepository->invalidateOpenTokensForUser($user, $now);

                $token = (new PasswordResetToken())
                    ->setUser($user)
                    ->setToken($tokenValue)
                    ->setCreatedAt($now)
                    ->setExpiresAt($now->modify('+2 hours'))
                    ->setUsedAt(null);

                $entityManager->persist($token);
                $entityManager->flush();

                $resetPath = $this->generateUrl('app_password_reset_reset', [
                    'token' => $tokenValue,
                ]);
                $resetUrl = rtrim($request->getSchemeAndHttpHost(), '/') . $resetPath;

                try {
                    $message = (new TemplatedEmail())
                        ->from($this->sender)
                        ->to($user->getEmail())
                        ->subject('Reinitialisation de votre mot de passe')
                        ->htmlTemplate('emails/password_reset.html.twig')
                        ->context([
                            'user' => $user,
                            'resetUrl' => $resetUrl,
                            'expiresAt' => $token->getExpiresAt(),
                        ]);

                    $mailer->send($message);
                } catch (TransportExceptionInterface) {
                    // Keep a neutral response to avoid user enumeration.
                }
            }

            $this->addFlash('success', 'Si un compte existe pour cet email, un lien de reinitialisation a ete envoye.');

            return $this->redirectToRoute('app_password_reset_request');
        }

        return $this->render('security/password_reset_request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/{token}', name: 'app_password_reset_reset', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        PasswordResetTokenRepository $tokenRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $tokenEntity = $tokenRepository->findActiveToken($token);
        if (!$tokenEntity instanceof PasswordResetToken) {
            $this->addFlash('error', 'Lien invalide ou expire.');

            return $this->redirectToRoute('app_password_reset_request');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $tokenEntity->getUser();
            if (!$user instanceof User) {
                $this->addFlash('error', 'Utilisateur introuvable pour ce lien.');

                return $this->redirectToRoute('app_password_reset_request');
            }

            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $now = new \DateTimeImmutable();
            $tokenEntity->setUsedAt($now);
            $tokenRepository->invalidateOpenTokensForUser($user, $now);

            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe mis a jour. Tu peux te connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset_reset.html.twig', [
            'resetForm' => $form,
            'expiresAt' => $tokenEntity->getExpiresAt(),
        ]);
    }
}
