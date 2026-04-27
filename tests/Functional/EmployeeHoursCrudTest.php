<?php

namespace App\Tests\Functional;

use App\Entity\Horaire;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class EmployeeHoursCrudTest extends FunctionalWebTestCase
{
    public function testHoursPageAccessByRole(): void
    {
        $user = $this->createUser('user-hours@test.local', ['ROLE_USER']);
        $employee = $this->createUser('employee-hours@test.local', ['ROLE_EMPLOYEE']);

        $guestClient = static::createClient();
        $guestClient->request('GET', '/employee/hours');
        self::assertResponseRedirects('/login');

        $userClient = $this->createClientAs($user);
        $userClient->request('GET', '/employee/hours');
        self::assertResponseStatusCodeSame(403);

        $employeeClient = $this->createClientAs($employee);
        $employeeClient->request('GET', '/employee/hours');
        self::assertResponseIsSuccessful();

        $hours = $this->entityManager->getRepository(Horaire::class)->findAll();
        self::assertCount(7, $hours);
    }

    public function testEmployeeCanUpdateHours(): void
    {
        $employee = $this->createUser('employee-hours-update@test.local', ['ROLE_EMPLOYEE']);
        $client = $this->createClientAs($employee);

        $client->request('GET', '/employee/hours');
        self::assertResponseIsSuccessful();

        $tokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
        $token = $tokenManager->getToken('employee_hours_update')->getValue();

        $payload = ['_token' => $token];
        for ($jour = 1; $jour <= 7; ++$jour) {
            $payload['ferme_' . $jour] = '1';
        }

        unset($payload['ferme_1']);
        $payload['ouverture_1'] = '09:00';
        $payload['fermeture_1'] = '18:00';

        $client->request('POST', '/employee/hours', $payload);
        self::assertResponseRedirects('/employee/hours');

        $this->entityManager->clear();
        /** @var list<Horaire> $allHours */
        $allHours = $this->entityManager->getRepository(Horaire::class)->findAll();
        self::assertCount(7, $allHours);

        $monday = $this->entityManager->getRepository(Horaire::class)->findOneBy(['jour' => 1]);
        self::assertInstanceOf(Horaire::class, $monday);
        self::assertFalse((bool) $monday->isFerme());
        self::assertSame('09:00', $monday->getHeureOuverture()?->format('H:i'));
        self::assertSame('18:00', $monday->getHeureFermeture()?->format('H:i'));

        $tuesday = $this->entityManager->getRepository(Horaire::class)->findOneBy(['jour' => 2]);
        self::assertInstanceOf(Horaire::class, $tuesday);
        self::assertTrue((bool) $tuesday->isFerme());
        self::assertNull($tuesday->getHeureOuverture());
        self::assertNull($tuesday->getHeureFermeture());
    }
}
