<?php

namespace App\Tests\Functional;

use App\Entity\Allergene;
use App\Entity\Plat;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class EmployeePlatAllergeneCrudTest extends FunctionalWebTestCase
{
    public function testPlatsAndAllergenesPagesAccessByRole(): void
    {
        $user = $this->createUser('user-plats@test.local', ['ROLE_USER']);
        $employee = $this->createUser('employee-plats@test.local', ['ROLE_EMPLOYEE']);

        $guestClient = static::createClient();
        $guestClient->request('GET', '/employee/plats');
        self::assertResponseRedirects('/login');

        $userClient = $this->createClientAs($user);
        $userClient->request('GET', '/employee/allergenes');
        self::assertResponseStatusCodeSame(403);

        $employeeClient = $this->createClientAs($employee);
        $employeeClient->request('GET', '/employee/plats');
        self::assertResponseIsSuccessful();

        $employeeClient->request('GET', '/employee/allergenes');
        self::assertResponseIsSuccessful();
    }

    public function testEmployeeCanCrudAllergeneAndPlat(): void
    {
        $employee = $this->createUser('employee-plat-crud@test.local', ['ROLE_EMPLOYEE']);
        $client = $this->createClientAs($employee);

        $crawler = $client->request('GET', '/employee/allergenes/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Creer')->form([
            'allergene[nom]' => 'Gluten',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/employee/allergenes');

        $this->entityManager->clear();
        $allergene = $this->entityManager->getRepository(Allergene::class)->findOneBy(['nom' => 'Gluten']);
        self::assertInstanceOf(Allergene::class, $allergene);

        $crawler = $client->request('GET', '/employee/plats/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Creer')->form([
            'plat[nom]' => 'Quiche lorraine',
            'plat[type]' => 'plat',
            'plat[description]' => 'Quiche test',
            'plat[allergenes]' => [(string) $allergene->getId()],
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/employee/plats');

        $this->entityManager->clear();
        $plat = $this->entityManager->getRepository(Plat::class)->findOneBy(['nom' => 'Quiche lorraine']);
        self::assertInstanceOf(Plat::class, $plat);
        self::assertCount(1, $plat->getAllergenes());

        $crawler = $client->request('GET', '/employee/plats/' . $plat->getId() . '/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'plat[nom]' => 'Quiche veggie',
            'plat[type]' => 'plat',
            'plat[description]' => 'Quiche modifiee',
            'plat[allergenes]' => [],
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/employee/plats');

        $this->entityManager->clear();
        $updatedPlat = $this->entityManager->getRepository(Plat::class)->find($plat->getId());
        self::assertInstanceOf(Plat::class, $updatedPlat);
        self::assertSame('Quiche veggie', $updatedPlat->getNom());

        $csrfTokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
        $platDeleteToken = $csrfTokenManager->getToken('delete_plat_' . $updatedPlat->getId())->getValue();

        $client->request('POST', '/employee/plats/' . $updatedPlat->getId() . '/delete', [
            '_token' => $platDeleteToken,
        ]);
        self::assertResponseRedirects('/employee/plats');

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Plat::class)->find($updatedPlat->getId()));

        $allergeneRefreshed = $this->entityManager->getRepository(Allergene::class)->findOneBy(['nom' => 'Gluten']);
        self::assertInstanceOf(Allergene::class, $allergeneRefreshed);

        $allergeneDeleteToken = $csrfTokenManager->getToken('delete_allergene_' . $allergeneRefreshed->getId())->getValue();
        $client->request('POST', '/employee/allergenes/' . $allergeneRefreshed->getId() . '/delete', [
            '_token' => $allergeneDeleteToken,
        ]);
        self::assertResponseRedirects('/employee/allergenes');

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Allergene::class)->find($allergeneRefreshed->getId()));
    }
}
