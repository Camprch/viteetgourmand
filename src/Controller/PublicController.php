<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactMessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.contact_recipient%')]
        private readonly string $contactRecipient,
        #[Autowire('%app.contact_sender%')]
        private readonly string $contactSender,
    ) {
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        $message = (new ContactMessage())
            ->setTraite(false)
            ->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ContactMessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            try {
                $email = (new TemplatedEmail())
                    ->from($this->contactSender)
                    ->to($this->contactRecipient)
                    ->replyTo((string) $message->getEmail())
                    ->subject('[Contact] '.(string) $message->getTitre())
                    ->htmlTemplate('emails/contact_message.html.twig')
                    ->context([
                        'contactMessage' => $message,
                    ]);

                $mailer->send($email);
            } catch (TransportExceptionInterface) {
                $this->addFlash('error', 'Message enregistre, mais email non envoye (probleme transport).');
            }

            $this->addFlash('success', 'Message envoye. Nous te repondrons rapidement.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('public/contact.html.twig', [
            'contactForm' => $form,
        ]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales', methods: ['GET'])]
    public function mentionsLegales(): Response
    {
        return $this->render('public/mentions_legales.html.twig');
    }

    #[Route('/cgv', name: 'app_cgv', methods: ['GET'])]
    public function cgv(): Response
    {
        return $this->render('public/cgv.html.twig');
    }
}
