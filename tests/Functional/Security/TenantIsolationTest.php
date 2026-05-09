<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Main\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TenantIsolationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[Test]
    public function twig_user_cannot_switch_to_cabinet_they_dont_own(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $emma = $em->getRepository(User::class)->findOneBy(['email' => 'emma@hotmail.com']);
        $this->client->loginUser($emma);

        // emma owns cabinets 1 & 2; cabinet 3 belongs to lucas
        $this->client->request('GET', '/app/cabinet/3');

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function twig_user_can_switch_to_their_own_cabinet(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $emma = $em->getRepository(User::class)->findOneBy(['email' => 'emma@hotmail.com']);
        $this->client->loginUser($emma);

        $this->client->request('GET', '/app/cabinet/1');

        self::assertResponseRedirects('/app');
    }

    #[Test]
    public function api_user_cannot_access_other_tenant_patients(): void
    {
        $token = $this->getJwtToken('emma@hotmail.com', 'Titi123!');

        $this->client->request(
            'GET',
            '/api/patients',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_X_TENANT_ID'   => '3', // emma doesn't own cabinet 3
            ],
        );

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function api_user_can_access_their_tenant_patients(): void
    {
        $token = $this->getJwtToken('emma@hotmail.com', 'Titi123!');

        $this->client->request(
            'GET',
            '/api/patients',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_X_TENANT_ID'   => '1',
            ],
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('member', $payload);
        self::assertGreaterThan(0, $payload['totalItems']);
    }

    private function getJwtToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => $password]),
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent(), true);

        return $payload['token'];
    }
}
