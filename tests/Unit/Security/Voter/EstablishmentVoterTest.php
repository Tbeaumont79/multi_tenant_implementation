<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Main\Establishment;
use App\Entity\Main\User;
use App\Security\Voter\EstablishmentVoter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EstablishmentVoterTest extends TestCase
{
    private EstablishmentVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new EstablishmentVoter();
    }

    #[Test]
    public function it_grants_when_user_is_member_of_establishment(): void
    {
        $user = new User();
        $establishment = new Establishment();
        $user->addEstablishment($establishment);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $establishment, [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[Test]
    public function it_denies_when_user_is_not_member(): void
    {
        $user = new User();
        $establishment = new Establishment(); // pas ajouté à $user

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $establishment, [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    public function it_denies_when_token_has_no_user(): void
    {
        $establishment = new Establishment();

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $establishment, [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    public function it_abstains_on_unsupported_subject(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $result = $this->voter->vote($token, new \stdClass(), [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function it_abstains_on_unsupported_attribute(): void
    {
        $establishment = new Establishment();
        $token = $this->createStub(TokenInterface::class);

        $result = $this->voter->vote($token, $establishment, ['DELETE']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
