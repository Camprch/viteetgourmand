<?php

namespace App\Tests\Functional;

final class RouteAccessByRoleTest extends FunctionalWebTestCase
{
    public function testRouteAccessIsRestrictedByRole(): void
    {
        $user = $this->createUser('user-role@test.local', ['ROLE_USER']);
        $employee = $this->createUser('employee-role@test.local', ['ROLE_EMPLOYEE']);
        $admin = $this->createUser('admin-role@test.local', ['ROLE_ADMIN']);

        $guestClient = static::createClient();
        $guestClient->request('GET', '/profile');
        self::assertResponseRedirects('/login');

        $userClient = $this->createClientAs($user);
        $userClient->request('GET', '/employee/orders');
        self::assertResponseStatusCodeSame(403);

        $employeeClient = $this->createClientAs($employee);
        $employeeClient->request('GET', '/employee/orders');
        self::assertResponseIsSuccessful();

        $employeeClient->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);

        $adminClient = $this->createClientAs($admin);
        $adminClient->request('GET', '/admin');
        self::assertResponseIsSuccessful();
    }
}
