<?php

namespace App\Tests\Functional;

use App\Entity\CommuneLivraison;
use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalWebTestCase extends WebTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        static::createClient();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        try {
            $this->ensureSafeTestDatabase();
            $this->resetDatabase();
        } catch (\PDOException $exception) {
            $this->markTestSkipped('Functional DB is unavailable: '.$exception->getMessage());
        }
    }

    protected function createClientAs(User $user): KernelBrowser
    {
        $client = static::createClient();
        $freshUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        if (!$freshUser instanceof User) {
            throw new \RuntimeException('User not found for authenticated client.');
        }

        $client->loginUser($freshUser);

        return $client;
    }

    protected function createUser(string $email, array $roles): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setPassword('$2y$13$dummyhashedpasswordvalueforfunctionaltests1234567890')
            ->setRoles($roles)
            ->setNom('Test')
            ->setPrenom('User')
            ->setTelephone('0600000000')
            ->setAdresse('1 rue de Test, Bordeaux')
            ->setActif(true)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createMenu(int $stock = 4): Menu
    {
        $menu = (new Menu())
            ->setTitre('Menu test')
            ->setDescription('Menu de test fonctionnel')
            ->setTheme('classique')
            ->setPrixMinCentimes(2000)
            ->setPersonnesMin(4)
            ->setConditionsParticulieres('Aucune')
            ->setRegime('classique')
            ->setStock($stock)
            ->setActif(true)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        return $menu;
    }

    protected function createCommune(string $distanceKm = '0.00'): CommuneLivraison
    {
        $commune = (new CommuneLivraison())
            ->setNom('Bordeaux')
            ->setCodePostal('33000')
            ->setDistanceKm($distanceKm)
            ->setActif(true);

        $this->entityManager->persist($commune);
        $this->entityManager->flush();

        return $commune;
    }

    protected function createCommande(User $user, Menu $menu, CommuneLivraison $commune): Commande
    {
        $commande = (new Commande())
            ->setUser($user)
            ->setMenu($menu)
            ->setCommuneLivraison($commune)
            ->setDateCommande(new \DateTimeImmutable())
            ->setDatePrestation(new \DateTimeImmutable('+3 days'))
            ->setHeurePrestation(new \DateTimeImmutable('12:30'))
            ->setAdressePrestation('1 rue de Test')
            ->setNomPrenomClient('Test User')
            ->setGsmClient('0600000000')
            ->setPrixMenuTotalCentimes(2000)
            ->setFraisLivraisonCentimes(0)
            ->setReductionAppliqueeCentimes(0)
            ->setPrixTotalCentimes(2000)
            ->setNbPersonnes(4)
            ->setPretMateriel(false);

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        return $commande;
    }

    private function resetDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if ($metadata === []) {
            return;
        }

        $tool = new SchemaTool($this->entityManager);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
        $this->entityManager->clear();
    }

    private function ensureSafeTestDatabase(): void
    {
        $params = $this->entityManager->getConnection()->getParams();
        $dbName = (string) ($params['dbname'] ?? '');

        if ($dbName === '' || !str_contains($dbName, '_test')) {
            throw new \RuntimeException(sprintf('Unsafe test database name "%s". Expected a *_test database.', $dbName));
        }

        $host = (string) ($params['host'] ?? '127.0.0.1');
        $port = (int) ($params['port'] ?? 3306);
        $user = (string) ($params['user'] ?? '');
        $password = (string) ($params['password'] ?? '');

        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
            $user,
            $password
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '``', $dbName)
        ));
    }
}
