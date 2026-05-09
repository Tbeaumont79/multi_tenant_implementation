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
        $this->client->loginUser($this->loadUser('emma@hotmail.com'));

        // emma owns cabinets 1 & 2; cabinet 3 belongs to lucas
        $this->client->request('GET', '/app/cabinet/3');

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function twig_user_can_switch_to_their_own_cabinet(): void
    {
        $this->client->loginUser($this->loadUser('emma@hotmail.com'));

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
        $payload = $this->decodeJsonResponse();
        self::assertArrayHasKey('member', $payload);
        self::assertGreaterThan(0, $payload['totalItems']);
    }

    private function loadUser(string $email): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, sprintf('Test fixture user "%s" missing.', $email));

        return $user;
    }

    private function getJwtToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $payload = $this->decodeJsonResponse();

        return $payload['token'];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
