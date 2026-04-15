<?php

namespace App\Tests\Functional;

use App\Entity\User;

final class ProfileEditTest extends FunctionalWebTestCase
{
    public function testUserCanUpdateProfileInformation(): void
    {
        $user = $this->createUser('profile-edit@test.local', ['ROLE_USER']);
        $client = $this->createClientAs($user);

        $crawler = $client->request('GET', '/profile/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'profile_edit[nom]' => 'Durand',
            'profile_edit[prenom]' => 'Camille',
            'profile_edit[email]' => 'profile-edited@test.local',
            'profile_edit[telephone]' => '0677777777',
            'profile_edit[adresse]' => '20 rue des Tests, Bordeaux',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/profile');

        $this->entityManager->clear();
        $updated = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertInstanceOf(User::class, $updated);
        self::assertSame('Durand', $updated->getNom());
        self::assertSame('Camille', $updated->getPrenom());
        self::assertSame('profile-edited@test.local', $updated->getEmail());
        self::assertSame('0677777777', $updated->getTelephone());
    }

    public function testUserCannotUseExistingEmail(): void
    {
        $user = $this->createUser('profile-owner@test.local', ['ROLE_USER']);
        $other = $this->createUser('already-used@test.local', ['ROLE_USER']);
        self::assertInstanceOf(User::class, $other);

        $client = $this->createClientAs($user);
        $crawler = $client->request('GET', '/profile/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'profile_edit[nom]' => 'Owner',
            'profile_edit[prenom]' => 'User',
            'profile_edit[email]' => 'already-used@test.local',
            'profile_edit[telephone]' => '0666666666',
            'profile_edit[adresse]' => '11 rue duplicate, Bordeaux',
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.flash-error', 'Cet email est deja utilise.');

        $this->entityManager->clear();
        $reloaded = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertInstanceOf(User::class, $reloaded);
        self::assertSame('profile-owner@test.local', $reloaded->getEmail());
    }
}
