<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Security\LoginSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class LoginSuccessHandlerTest extends TestCase
{
    public function testUsesTargetPathWhenPresent(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::never())->method('generate');

        $handler = new LoginSuccessHandler($urlGenerator);

        $request = Request::create('/login');
        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/orders/new/12');
        $request->setSession($session);

        $user = $this->createUserWithRoles(['ROLE_USER']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $response = $handler->onAuthenticationSuccess($request, $token);
        self::assertNotNull($response);
        self::assertSame('/orders/new/12', $response->headers->get('Location'));
    }

    public function testRedirectsAdminToAdminDashboard(): void
    {
        $this->assertRoleRedirect(['ROLE_ADMIN'], 'app_admin_dashboard', '/admin');
    }

    public function testRedirectsEmployeeToEmployeeDashboard(): void
    {
        $this->assertRoleRedirect(['ROLE_EMPLOYEE'], 'app_employee_dashboard', '/employee');
    }

    public function testRedirectsUserToProfile(): void
    {
        $this->assertRoleRedirect(['ROLE_USER'], 'app_profile', '/profile');
    }

    /**
     * @param list<string> $roles
     */
    private function assertRoleRedirect(array $roles, string $expectedRoute, string $generatedPath): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with($expectedRoute)
            ->willReturn($generatedPath);

        $handler = new LoginSuccessHandler($urlGenerator);
        $request = Request::create('/login');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $user = $this->createUserWithRoles($roles);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $response = $handler->onAuthenticationSuccess($request, $token);
        self::assertNotNull($response);
        self::assertSame($generatedPath, $response->headers->get('Location'));
    }

    /**
     * @param list<string> $roles
     */
    private function createUserWithRoles(array $roles): User
    {
        return (new User())
            ->setEmail('login-success@test.local')
            ->setRoles($roles)
            ->setPassword('hash')
            ->setNom('User')
            ->setPrenom('Test')
            ->setActif(true)
            ->setCreatedAt(new \DateTimeImmutable());
    }
}
