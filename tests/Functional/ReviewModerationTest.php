<?php

namespace App\Tests\Functional;

use App\Entity\Avis;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ReviewModerationTest extends FunctionalWebTestCase
{
    public function testEmployeeCanValidateReview(): void
    {
        $employee = $this->createUser('employee-review@test.local', ['ROLE_EMPLOYEE']);
        $customer = $this->createUser('customer-review@test.local', ['ROLE_USER']);
        $menu = $this->createMenu();
        $commune = $this->createCommune();
        $commande = $this->createCommande($customer, $menu, $commune);

        $avis = (new Avis())
            ->setCommande($commande)
            ->setNote(5)
            ->setCommentaire('Excellent service et tres bon menu.')
            ->setValide(false)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($avis);
        $this->entityManager->flush();

        $csrfTokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
        $csrfToken = $csrfTokenManager->getToken('moderate_review_' . $avis->getId())->getValue();

        $client = $this->createClientAs($employee);
        $client->request('POST', '/employee/reviews/' . $avis->getId() . '/moderate', [
            '_token' => $csrfToken,
            'action' => 'validate',
        ]);

        self::assertResponseRedirects('/employee/reviews');

        $this->entityManager->clear();
        $avisUpdated = $this->entityManager->getRepository(Avis::class)->find($avis->getId());

        self::assertInstanceOf(Avis::class, $avisUpdated);
        self::assertTrue((bool) $avisUpdated->isValide());
    }
}
